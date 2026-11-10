<form method="POST" enctype="multipart/form-data" action="{{ route('admin.project-settings.birthday-background.store') }}"
      data-no-spinner data-background-confirm="Save this birthday background and display mode?">
    @csrf
    <div class="project-settings-panel-header">
        <h5>Birthday background</h5>
        <div class="text-muted small mt-1">One shared image for Technician birthdays. You can replace it at any time.</div>
    </div>
    <div class="project-settings-panel-body">
        <div class="user-background-grid">
            <div class="user-background-controls">
                <label class="form-label fw-semibold" for="birthdayBackgroundAudience">Display mode</label>
                <select id="birthdayBackgroundAudience" name="mode" class="form-select mb-2">
                    <option value="birthday_person" @selected(old('mode', $birthdayBackground['mode'] ?? 'off') === 'birthday_person')>Birthday Technician only</option>
                    <option value="birthday_and_everyone" @selected(old('mode', $birthdayBackground['mode'] ?? 'off') === 'birthday_and_everyone')>Birthday Technician + all employees</option>
                    <option value="off" @selected(old('mode', $birthdayBackground['mode'] ?? 'off') === 'off')>Off — no birthday backgrounds</option>
                </select>
                <p id="birthdayBackgroundSchedule" class="form-text"></p>
                <label class="form-label fw-semibold mt-2" for="birthdayBackgroundImage">Birthday image</label>
                <input type="file" id="birthdayBackgroundImage" name="birthday_image" class="form-control"
                       accept="image/jpeg,image/png,image/webp">
                <div class="form-text">JPG, PNG or WebP, up to 10 MB. Choose a landscape image. Leave empty to keep the saved image.</div>
                @error('birthday_image')<div class="text-danger mt-2">{{ $message }}</div>@enderror
                @if(!empty($birthdayBackground['original_name']))
                    <div class="small mt-3 user-background-file-name">Saved: {{ $birthdayBackground['original_name'] }}</div>
                @endif
                <p class="text-muted small mt-3 mb-0">Uses the birthday in each Technician’s profile and Toronto calendar dates. February 29 is observed on February 28 in non-leap years. The usual background returns automatically afterwards.</p>
            </div>
            <div class="user-background-preview-wrap">
                <div class="user-background-preview" @if(!$birthdayBackgroundPreview) hidden @endif>
                    <img id="birthdayBackgroundPreview" @if($birthdayBackgroundPreview) src="{{ $birthdayBackgroundPreview }}" @endif
                         data-saved-url="{{ $birthdayBackgroundPreview ?? '' }}" alt="Birthday background preview">
                </div>
                @if(!$birthdayBackgroundPreview)
                    <div id="birthdayBackgroundEmptyHint" class="text-muted small mt-2">Choose an image to preview it before saving.</div>
                @endif
            </div>
        </div>
    </div>
    <div class="project-settings-panel-footer">
        <button type="submit" class="btn btn-outline-primary">Save birthday background</button>
    </div>
</form>
