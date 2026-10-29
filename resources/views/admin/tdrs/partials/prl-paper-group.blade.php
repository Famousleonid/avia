@php($paperCountBadgeStyle = 'position: absolute; top: -5px; left: 2px; min-width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; font-size: .7rem; padding: 0 5px;')
<div class="tdr-show-paper-extra ms-2" id="tdr-prl-paper-group">
                            @if(($kitPrlCount ?? 0) > 0)
                                <div class="position-relative d-inline-block ">
                                    <x-paper-button text="KIT"
                                                    href="{{ route('tdrs.kitForm', ['id' => $current_wo->id]) }}"
                                                    target="_blank" color="outline-primary"/>
                                    <span class="badge bg-success rounded-pill"
                                          style="{{ $paperCountBadgeStyle }}">{{ $kitPrlCount }}</span>
                                </div>
                            @endif
                            @if(($prlPartsCount ?? 0) > 0)
                                <div class="position-relative d-inline-block ">
                                    <x-paper-button text="PRL"
                                                    href="{{ route('tdrs.prlForm', ['id' => $current_wo->id]) }}"
                                                    target="_blank" color="outline-primary"/>
                                    <span class="badge bg-success rounded-pill"
                                          style="{{ $paperCountBadgeStyle }}">{{ $prlPartsCount }}</span>
                                </div>
                            @endif
                            <button type="button" id="woAssyConfigurationOpen"
                                    class="btn btn-outline-info btn-sm d-none align-self-center"
                                    data-index-url="{{ route('workorders.assy-configuration.index', $current_wo) }}"
                                    data-update-url="{{ route('workorders.assy-configuration.update', $current_wo) }}"
                                    data-locked="{{ $current_wo->done_at ? '1' : '0' }}">
                                {{ __('ASSY composition') }} <span id="woAssyConfigurationPending" class="badge bg-warning text-dark d-none"></span>
                            </button>
                        </div>
