{{-- resources/views/pdf/table.blade.php --}}
    <!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width:100%; border-collapse: collapse; }
        th, td { border:1px solid #000; padding:6px; }
        th { background: #eee; }
    </style>
</head>
<body>

<h3 style="margin:0 0 10px;">Report Table</h3>

<table>
    <thead>
    <tr>
        <th style="width:5%">Number</th>
        <th style="width:15%">Component</th>
        <th style="width:15%">Serial number</th>
        <th style="width:25%">Customer</th>
        <th style="width:25%">Instruction</th>
        <th style="width:15%">Open Date</th>

    </tr>
    </thead>
    <tbody>
    @foreach($rows as $i => $row)
        <tr>
            <td>{{ $row->number }}</td>
            <td>{{ $row->unit->part_number }}</td>
            <td>{{ $row->serial_number }}</td>
            <td>{{ $row->customer->name }}</td>
            <td>{{ $row->instruction->name }}</td>
            <td>
                {{ $row->open_at ? strtolower($row->open_at->format('d-M-Y')) : '' }}
            </td>
        </tr>
    @endforeach
    </tbody>
</table>

</body>
</html>
