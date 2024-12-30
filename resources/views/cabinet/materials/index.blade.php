@extends('cabinet.master')

@section('content')
    <style>
        table#show-materials th {
            text-align: left;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .editable {
            cursor: pointer;
            border: 1px dashed transparent;
            text-align: left;
            padding: 0 10px;
        }
        .editable:hover {
            border-color: #007bff;
        }
        .hidden-tbody {
            visibility: hidden;
        }

        table#show-materials td,
        table#show-materials th {
            padding: 0 10px;
            text-align: left;
        }

        /* Light theme styles */
        html[data-bs-theme="light"] table#show-materials tbody tr:nth-child(odd) {
            background-color: #f8f9fa;
        }

        html[data-bs-theme="light"] table#show-materials tbody tr:hover {
            background-color: #e9ecef;
        }

        /* Dark theme styles */
        html[data-bs-theme="dark"] table#show-materials tbody tr:nth-child(odd) {
            background-color: #1a1a1a;
        }

        html[data-bs-theme="dark"] table#show-materials tbody tr:hover {
            background-color: #505151;
        }
    </style>

    <section class="container-fluid mt-2">
        <div class="card p-2 shadow">
            <div class="card-body p-0">
                <h4 class="ml-3">All materials: <span class="text-primary" id="orders_count">{{ count($materials) }}</span></h4>
                @if(count($materials))
                    <table data-page-length="35" id="show-materials" class="display table-sm table-bordered table-striped table-hover theme-sensitive w-100">
                        <thead class="bg-gradient">
                        <tr>
                            <th class="text-primary bg-gradient" data-sort="true">CML Code</th>
                            <th class="text-primary bg-gradient" data-orderable="false">Material</th>
                            <th class="text-primary bg-gradient">Specification</th>
                            <th class="text-primary bg-gradient">Description</th>
                        </tr>
                        </thead>
                        <tbody class="hidden-tbody">
                        @foreach ($materials as $material)
                            <tr>
                                <td>{{ $material->code }}</td>
                                <td>{{ $material->material }}</td>
                                <td>{{ $material->specification }}</td>
                                <td class="editable" data-id="{{ $material->id }}" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ $material->description }}" data-field="description">{{ $material->description }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @else
                    <p>Materials not created</p>
                @endif
            </div>
        </div>
    </section>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const table = $('#show-materials');
            const tbody = document.querySelector('#show-materials tbody');

            table.DataTable({
                autoWidth: true,
                scrollY: "650px",
                scrollCollapse: true,
                paging: false,
                info: false,
                order: [[0, 'asc']],
                columnDefs: [
                    { targets: 0, width: "20%" },
                    { targets: 1, visible: false },
                    { targets: 2, width: "40%" },
                    { targets: 3, width: "40%" }
                ],
                initComplete: function () {
                    tbody.classList.remove('hidden-tbody');
                },
                responsive: true
            });

            window.addEventListener('resize', function () {
                if (window.innerWidth < 1200) {
                    table.DataTable().column(1).visible(false);
                } else {
                    table.DataTable().column(1).visible(true);
                }
            });

            //----------------- Editable Description ----------------------------------

            function attachEditableHandlers() {
                document.querySelectorAll('.editable').forEach(cell => {
                    cell.addEventListener('click', function () {
                        if (this.classList.contains('editing')) return;

                        const originalText = this.innerText.trim();
                        const input = document.createElement('input');
                        input.type = 'text';
                        input.value = originalText;
                        input.className = 'form-control';

                        this.innerHTML = '';
                        this.appendChild(input);
                        this.classList.add('editing');

                        input.focus();

                        input.addEventListener('blur', () => {
                            const newText = input.value.trim();
                            this.classList.remove('editing');

                            if (newText === '') {
                                this.innerHTML = '&nbsp;';
                            } else {
                                this.innerText = newText;
                            }

                            if (newText !== originalText) {
                                const id = this.dataset.id;
                                fetch(`/cabinet/materials/${id}`, {
                                    method: 'PUT',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                    },
                                    body: JSON.stringify({ description: newText })
                                }).catch(() => this.innerText = originalText);
                            }
                        });
                    });
                });
            }

            attachEditableHandlers();

            //----------------- End Editable Description ----------------------------------
        });
    </script>
@endsection
