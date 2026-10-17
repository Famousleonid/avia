"""Read extracted workbook + read-only DB snapshot; emit review manifest, never SQL."""
import argparse
import json
import re
from pathlib import Path

parser = argparse.ArgumentParser()
parser.add_argument('workbook')
parser.add_argument('snapshot')
parser.add_argument('output')
args = parser.parse_args()
book = json.loads(Path(args.workbook).read_text(encoding='utf-8-sig'))
db = json.loads(Path(args.snapshot).read_text(encoding='utf-8-sig'))
manual = db['manual']['number']
out = {'source': book['source'], 'sha256': book['sha256'], 'manual': db['manual'], 'sheets': []}
for sheet in book['sheets']:
    cells = {c['cell']: c for row in sheet['cells'] for c in row}
    def value(coord):
        c = cells.get(coord, {})
        return c.get('cached') if c.get('type') == 'f' else c.get('value')
    item = {'sheet': sheet['name'], 'classification': 'other form: review separately'}
    if str(value('A2')).strip().upper() == 'PART TRAVELER':
        pn = str(value('K5') or '').strip()
        matches = [c for c in db['components'] if c['part_number'].strip() == pn]
        item.update(classification='PART TRAVELER', pn=pn, source_description=value('G5'),
                    cmm=value('D7'), matches=matches, issues=[], steps=[])
        if not matches:
            item['issues'].append('No exact full-P/N match; do not expand wildcard automatically')
        if manual not in str(value('D7')):
            item['issues'].append('CMM identity differs or is missing')
        for row in range(10, sheet['rows'] + 1):
            if not isinstance(value(f'A{row}'), (int, float)) or not value(f'B{row}'):
                continue
            task, spec = str(value(f'B{row}')), str(value(f'E{row}') or '')
            step = {'source_row': row, 'sequence': value(f'A{row}'), 'task': task,
                    'specification': spec, 'vendor_context': value(f'H{row}'), 'note': value(f'K{row}')}
            item['steps'].append(step)
            if '__' in spec:
                item['issues'].append(f'E{row}: incomplete technical placeholder')
            if re.search(r'\bif\b', task + ' ' + spec, re.I):
                item['issues'].append(f'B{row}/E{row}: conditional operation; preserve condition')
            for ref in set(re.findall(r'\d{2}-\d{2}-\d{2}RM', spec)):
                if ref != manual:
                    item['issues'].append(f'E{row}: reference to other manual {ref}')
            if any(cells.get(f'{col}{row}', {}).get('strike') for col in ('A', 'B', 'E')):
                item['issues'].append(f'Row {row}: source strikeout requires review')
        print(f"{sheet['name']} | PN={pn} | matches={len(matches)} | steps={len(item['steps'])} | issues={len(item['issues'])}")
    out['sheets'].append(item)
Path(args.output).write_text(json.dumps(out, ensure_ascii=False, indent=2), encoding='utf-8')
