                <form method="POST" action="{{ route('trainings.store') }}" id="training_create_form">
                    @csrf
                    <input type="hidden" name="return_url" value="{{ request()->get('return_url', url()->previous()) }}">
                    @if(isset($userId) && $userId != auth()->id())
                        <input type="hidden" name="user_id" value="{{ $userId }}">
                    @endif
                    @php $canMarkLegacy = auth()->user()->canManageTrainingMatrix(); @endphp
                    @if($canMarkLegacy)
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" id="is_legacy" name="is_legacy" value="1">
                            <label class="form-check-label" for="is_legacy">
                                {{ __('Old training') }}
                                <small class="text-muted d-block">{{ __('Trained in the past, no dated records. Shown as «X» in the matrix; no Form 112/132.') }}</small>
                            </label>
                        </div>
                    @endif

                    <div class="form-group mt-2" id="single_manual_group">
                        <label for="manuals_id">{{ __('Component PN') }}</label>
                        <select id="manuals_id" name="manuals_id" class="form-control" required>
                            <option value="">{{ __('Select Component PN') }}</option>
                            @foreach ($manuals as $manual)
                                @if(!empty($manual->unit_name_training))
                                    <option value="{{ $manual->id }}" data-training-hours="{{ (float) $manual->training_hours }}"
                                            @if(request('manual_id') == $manual->id) selected @endif>
                                        {{ $manual->unit_name_training }}
                                        ({{ $manual->title }})
                                    </option>
                                @endif
                            @endforeach
                            @if(request()->boolean('modal'))
                                @foreach($addedManuals as $manual)
                                    <option value="{{ $manual->id }}" data-already-added="1">{{ $manual->unit_name_training }} ({{ $manual->title }}) — {{ __('Already in the component table. Click to show.') }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    @if($canMarkLegacy)
                        <div class="form-group mt-2" id="legacy_manuals_group" style="display: none;">
                            <label for="legacy_manuals_ids">{{ __('Units (multiple selection)') }}</label>
                            <select id="legacy_manuals_ids" name="legacy_manuals_ids[]" class="form-control" multiple size="12">
                                @foreach ($manuals as $manual)
                                    @if(!empty($manual->unit_name_training))
                                        <option value="{{ $manual->id }}">
                                            {{ $manual->unit_name_training }} ({{ $manual->title }})
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                            <small class="form-text text-muted">{{ __('Hold Ctrl to select multiple units.') }}</small>

                            {{-- Бланк 132 потерян / нужен перевыпуск: создать 132 вместе с отметкой --}}
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" id="legacy_create_132" name="create_form_132" value="1">
                                <label class="form-check-label" for="legacy_create_132">
                                    {{ __('Also create Form 132 (lost / needs reissue)') }}
                                </label>
                            </div>
                            <div class="mt-1" id="legacy_132_date_group" style="display: none;">
                                <label for="legacy_132_date" class="form-label">{{ __('Form 132 date') }}</label>
                                <input type="date" id="legacy_132_date" name="form_132_date" class="form-control"
                                       value="{{ now()->format('Y-m-d') }}" max="{{ now()->format('Y-m-d') }}">
                                <small class="form-text text-muted">{{ __('The date is normalized to the Friday of its week.') }}</small>
                            </div>
                        </div>
                    @endif

                    <div class="form-group mt-3" id="date_training_group">
                        <label for="date_training">{{ __('First Training Date') }}</label>
                        {{-- data-project-date: проектный flatpickr, формат 24/Aug/2026 --}}
                        <input type="text" id="date_training" name="date_training" class="form-control"
                               data-project-date autocomplete="off" placeholder="{{ __('dd/Mon/yyyy') }}" required>
                    </div>

                    <div class="form-group mt-3">
                        <div id="training_dates_list"></div>
                        <button type="button" class="btn btn-outline-secondary btn-sm mt-1" id="add_training_date_btn">
                            <i class="bi bi-plus"></i> {{ __('Add Date') }}
                        </button>
                        <div id="training_dates_error" class="text-danger mt-1" style="display: none;"></div>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-primary mt-3">{{ __('Add Component') }}</button>
                        @php
                            // «Back to TDR» — только если реально пришли из TDR/Main
                            // (из матрицы тоже приходит manual_id, но там возврат делает Cancel)
                            $cameFromTdr = str_contains(request('return_url', ''), '/tdrs/')
                                || str_contains(request('return_url', ''), '/mains/');
                        @endphp
                        @if(request('manual_id') && $cameFromTdr)
                            <a href="{{ request()->get('return_url', url()->previous()) }}" class="btn btn-outline-info mt-3">
                                <i class="bi bi-arrow-left"></i> Back to TDR
                            </a>
                        @endif
                        @php
                            // Cancel возвращает туда, откуда пришли (матрица/TDR), но только внутри приложения
                            $cancelUrl = request('return_url');
                            if (!$cancelUrl || !\Illuminate\Support\Str::startsWith($cancelUrl, url('/'))) {
                                $cancelUrl = route('trainings.index', isset($userId) && $userId != auth()->id() ? ['user_id' => $userId] : []);
                            }
                        @endphp
                        <a href="{{ $cancelUrl }}" class="btn btn-outline-secondary mt-3">{{ __('Cancel') }} </a>
                    </div>
                </form>
