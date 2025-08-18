@extends('admin.master')

@section('content')
    <div class="card shadow">
        <div class="card-header my-1 shadow d-flex justify-content-between align-items-center">
            <h5 class="text-primary manage-header">{{__('Manual')}}: {{$manual->number}} — {{$manual->title}}</h5>
            <div>
                <a href="{{ route('components.create', ['manual_id' => $manual->id, 'redirect' => request()->fullUrl()]) }}" class="btn btn-outline-primary me-2">{{ __('Add Component') }}</a>
                <a href="{{ route('components.index') }}" class="btn btn-outline-secondary">{{ __('Back') }}</a>
            </div>
        </div>

        @if($components->count())
            <div class="table-responsive p-2">
                <table class="table table-sm table-hover table-striped align-middle table-bordered">
                    <thead>
                    <tr>
                        <th class="text-center">{{ __('IPL Number') }}</th>
                        <th class="text-center">{{ __('Component Description') }}</th>
                        <th class="text-center">{{ __('Part Number') }}</th>
                        <th class="text-center">{{ __('Image') }}</th>
                        <th class="text-center">{{ __('Assy') }}</th>
                        <th class="text-center">{{ __('Action') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($components as $component)
                        <tr>
                            <td class="text-center">{{$component->ipl_num}}</td>
                            <td class="text-center">{{$component->name}}</td>
                            <td class="text-center">{{$component->part_number}}</td>
                            <td class="text-center" style="width: 120px;">
                                <a href="{{ $component->getFirstMediaBigUrl('components') }}" data-fancybox="gallery">
                                    <img class="rounded-circle" src="{{ $component->getFirstMediaThumbnailUrl('components') }}" width="40" height="40" alt="IMG"/>
                                </a>
                            </td>
                            <td class="text-center" style="width: 120px;">
                                <a href="{{ $component->getFirstMediaBigUrl('assy_components') }}" data-fancybox="gallery">
                                    <img class="rounded-circle" src="{{ $component->getFirstMediaThumbnailUrl('assy_components') }}" width="40" height="40" alt="IMG"/>
                                </a>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('components.edit',['component' => $component->id]) }}" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                                <form action="{{ route('components.destroy', $component->id) }}" method="POST" style="display:inline-block;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Вы уверены, что хотите удалить этот компонент?');">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <h5 class="text-center p-3">{{ __('No components for this manual') }}</h5>
        @endif
    </div>
@endsection


