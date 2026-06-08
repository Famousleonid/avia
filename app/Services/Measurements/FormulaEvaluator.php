<?php

namespace App\Services\Measurements;

/**
 * Safe arithmetic expression evaluator for process document formulas.
 *
 * Level 1 — supported:
 *   - Float literals:        0.7128  /  3  /  .5
 *   - Parameter tokens:      [p:45]   → replaced with float before parsing
 *   - Operators:             +  -  *  /  ^  (power)
 *   - Grouping:              (  )
 *   - Unary minus:           -(expr)  /  -3.14
 *   - Math functions:        sqrt(x)  abs(x)  round(x,n)  floor(x)  ceil(x)
 *                            pow(x,y)  min(x,y)  max(x,y)
 *
 * Future (Level 2): multi-step with named variables (a = expr; result = ...)
 * Future (Level 3): conditional expressions (if x > y then a else b)
 *
 * Usage:
 *   $result = FormulaEvaluator::evaluate('0.7128 - [p:45]', [45 => 0.4372]);
 *   $result = FormulaEvaluator::evaluate('sqrt([p:1]^2 + [p:2]^2)', [1 => 3.0, 2 => 4.0]);
 *   // → 5.0
 *
 * Throws \RuntimeException on syntax errors or division by zero.
 * Returns null if any referenced parameter value is null (measurement missing).
 */
class FormulaEvaluator
{
    private string $expr;
    private int    $pos;
    private int    $len;

    /**
     * @param  string         $expression   e.g. "0.7128 - [p:45]"
     * @param  array<int,float|null> $paramValues  [paramId => actualValue|null]
     * @return float|null     null if any param value is missing
     */
    public static function evaluate(string $expression, array $paramValues): ?float
    {
        // Replace [p:ID] tokens with their float values
        $resolved = preg_replace_callback('/\[p:(\d+)\]/', function ($m) use ($paramValues) {
            $id  = (int) $m[1];
            $val = $paramValues[$id] ?? null;
            if ($val === null) {
                throw new \RuntimeException('__MISSING__');
            }

            return (string) (float) $val;
        }, $expression);

        try {
            $ev     = new self($resolved);
            $result = $ev->parseExpr();
            $ev->skipWs();
            if ($ev->pos < $ev->len) {
                throw new \RuntimeException('Unexpected token at position ' . $ev->pos);
            }

            return $result;
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === '__MISSING__') {
                return null;
            }
            throw $e;
        }
    }

    private function __construct(string $expr)
    {
        $this->expr = $expr;
        $this->pos  = 0;
        $this->len  = strlen($expr);
    }

    // expression = term (('+' | '-') term)*
    private function parseExpr(): float
    {
        $left = $this->parseTerm();
        while (true) {
            $this->skipWs();
            if ($this->pos < $this->len && $this->expr[$this->pos] === '+') {
                $this->pos++;
                $left += $this->parseTerm();
            } elseif ($this->pos < $this->len && $this->expr[$this->pos] === '-') {
                $this->pos++;
                $left -= $this->parseTerm();
            } else {
                break;
            }
        }

        return $left;
    }

    // term = power (('*' | '/') power)*
    private function parseTerm(): float
    {
        $left = $this->parsePower();
        while (true) {
            $this->skipWs();
            if ($this->pos < $this->len && $this->expr[$this->pos] === '*') {
                $this->pos++;
                $left *= $this->parsePower();
            } elseif ($this->pos < $this->len && $this->expr[$this->pos] === '/') {
                $this->pos++;
                $right = $this->parsePower();
                if ($right == 0.0) {
                    throw new \RuntimeException('Division by zero');
                }
                $left /= $right;
            } else {
                break;
            }
        }

        return $left;
    }

    // power = factor ('^' factor)*   (right-associative)
    private function parsePower(): float
    {
        $base = $this->parseFactor();
        $this->skipWs();
        if ($this->pos < $this->len && $this->expr[$this->pos] === '^') {
            $this->pos++;
            $exp = $this->parsePower(); // right-associative recursion

            return (float) ($base ** $exp);
        }

        return $base;
    }

    // factor = unary | function call | '(' expr ')' | number
    private function parseFactor(): float
    {
        $this->skipWs();
        if ($this->pos >= $this->len) {
            throw new \RuntimeException('Unexpected end of expression');
        }

        // Unary minus
        if ($this->expr[$this->pos] === '-') {
            $this->pos++;

            return -$this->parseFactor();
        }

        // Unary plus (ignore)
        if ($this->expr[$this->pos] === '+') {
            $this->pos++;

            return $this->parseFactor();
        }

        // Parenthesised sub-expression
        if ($this->expr[$this->pos] === '(') {
            $this->pos++; // consume '('
            $val = $this->parseExpr();
            $this->skipWs();
            if ($this->pos >= $this->len || $this->expr[$this->pos] !== ')') {
                throw new \RuntimeException('Missing closing parenthesis');
            }
            $this->pos++; // consume ')'

            return $val;
        }

        // Named function call: name '(' args... ')'
        if ($this->pos < $this->len && ctype_alpha($this->expr[$this->pos])) {
            return $this->parseFunction();
        }

        // Number
        return $this->parseNumber();
    }

    /** Parse a named function call: name(arg [, arg]*) */
    private function parseFunction(): float
    {
        // Read function name
        $start = $this->pos;
        while ($this->pos < $this->len && (ctype_alpha($this->expr[$this->pos]) || $this->expr[$this->pos] === '_')) {
            $this->pos++;
        }
        $name = strtolower(substr($this->expr, $start, $this->pos - $start));

        $this->skipWs();
        if ($this->pos >= $this->len || $this->expr[$this->pos] !== '(') {
            throw new \RuntimeException('Expected "(" after function name "' . $name . '"');
        }
        $this->pos++; // consume '('

        // Parse comma-separated arguments
        $args = [];
        $this->skipWs();
        if ($this->pos < $this->len && $this->expr[$this->pos] !== ')') {
            $args[] = $this->parseExpr();
            $this->skipWs();
            while ($this->pos < $this->len && $this->expr[$this->pos] === ',') {
                $this->pos++; // consume ','
                $args[] = $this->parseExpr();
                $this->skipWs();
            }
        }
        if ($this->pos >= $this->len || $this->expr[$this->pos] !== ')') {
            throw new \RuntimeException('Missing closing ")" for function "' . $name . '"');
        }
        $this->pos++; // consume ')'

        return $this->callFunction($name, $args);
    }

    private function callFunction(string $name, array $args): float
    {
        switch ($name) {
            case 'sqrt':
                $this->assertArgCount($name, $args, 1);
                if ($args[0] < 0) throw new \RuntimeException('sqrt() of negative number');
                return (float) sqrt($args[0]);

            case 'abs':
                $this->assertArgCount($name, $args, 1);
                return (float) abs($args[0]);

            case 'round':
                if (count($args) < 1 || count($args) > 2) {
                    throw new \RuntimeException('round() takes 1 or 2 arguments');
                }
                $decimals = isset($args[1]) ? (int) $args[1] : 0;
                return (float) round($args[0], $decimals);

            case 'floor':
                $this->assertArgCount($name, $args, 1);
                return (float) floor($args[0]);

            case 'ceil':
                $this->assertArgCount($name, $args, 1);
                return (float) ceil($args[0]);

            case 'pow':
                $this->assertArgCount($name, $args, 2);
                return (float) ($args[0] ** $args[1]);

            case 'min':
                if (count($args) < 2) throw new \RuntimeException('min() requires at least 2 arguments');
                return (float) min(...$args);

            case 'max':
                if (count($args) < 2) throw new \RuntimeException('max() requires at least 2 arguments');
                return (float) max(...$args);

            default:
                throw new \RuntimeException('Unknown function "' . $name . '"');
        }
    }

    private function assertArgCount(string $name, array $args, int $expected): void
    {
        if (count($args) !== $expected) {
            throw new \RuntimeException(
                $name . '() expects ' . $expected . ' argument(s), got ' . count($args)
            );
        }
    }

    private function parseNumber(): float
    {
        $this->skipWs();
        $start = $this->pos;
        // optional digits, optional dot, optional digits, optional exponent
        while ($this->pos < $this->len && (
            ctype_digit($this->expr[$this->pos])
            || $this->expr[$this->pos] === '.'
            || (in_array($this->expr[$this->pos], ['e', 'E'], true) && $this->pos > $start)
        )) {
            $this->pos++;
        }
        if ($this->pos === $start) {
            throw new \RuntimeException('Expected number at position ' . $this->pos . ', got "' . substr($this->expr, $this->pos, 5) . '"');
        }

        return (float) substr($this->expr, $start, $this->pos - $start);
    }

    private function skipWs(): void
    {
        while ($this->pos < $this->len && $this->expr[$this->pos] === ' ') {
            $this->pos++;
        }
    }
}
