@extends('cabinet.master')

@section('content')

    <section class="container pl-3 pr-3 mt-2">
        <div class="card firm-border p-2 bg-white shadow">

            <input type="file" id="input-excel"/>
            <button id="print-excel">Print</button>
            <div id="excel-output"></div>


        </div>
    </section>

@endsection

@section('scripts')

    <script>
        document.getElementById('input-excel').addEventListener('change', function (e) {
            const file = e.target.files[0];
            const reader = new FileReader();
            reader.onload = function (event) {
                const data = new Uint8Array(event.target.result);
                const workbook = XLSX.read(data, {type: 'array'});
                const sheetName = workbook.SheetNames[0];
                const sheet = workbook.Sheets[sheetName];
                const html = XLSX.utils.sheet_to_html(sheet);
                document.getElementById('excel-output').innerHTML = html;
            };
            reader.readAsArrayBuffer(file);
        });

        document.getElementById('print-excel').addEventListener('click', function () {
            const content = document.getElementById('excel-output').innerHTML;
            const printWindow = window.open('', '', 'height=500, width=500');
            printWindow.document.write('<html><head><title>Print Excel</title>');
            printWindow.document.write('</head><body>');
            printWindow.document.write(content);
            printWindow.document.write('</body></html>');
            printWindow.document.close();
            printWindow.print();
        });

    </script>

@endsection



