<style>
    #editCPForm .process-specification-column { width: 100%; min-width: 0; }
    #editCPForm .process-extra-fields:not(:has(.process-notes-field:not(.d-none), #ec-checkbox-container[style*="display: block"])) { display: none; }
    #editCPForm .edit-spec-grid { display: grid; grid-template-columns: minmax(0, 1fr); gap: 12px; }
    #editCPForm .process-options:not(.show-descriptions) .edit-spec-descriptions { display: none; }
    #editCPForm .process-options.show-descriptions .edit-spec-list,
    #editCPForm .process-options.show-descriptions input[type="search"] { display: none; }
    #editCPForm .edit-spec-list, #editCPForm .edit-spec-descriptions {
        min-width: 0;
        max-height: min(420px, 50dvh);
        overflow-y: auto;
        overflow-wrap: anywhere;
        overscroll-behavior: contain;
    }
    #editCPForm .edit-spec-list .form-check { margin-bottom: 8px; }
    #editCPForm .edit-spec-description { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 12px; align-items: start; margin-bottom: 12px; }
    #editCPForm .edit-spec-description > span { display: block; margin-bottom: 4px; white-space: pre-line; }
    #editCPForm .edit-spec-description textarea { min-height: 84px; flex: none; }
    @media (max-width: 767px) {
        #editCPForm .edit-spec-description { grid-template-columns: minmax(0, 1fr); }
        #editCPForm .edit-spec-list, #editCPForm .edit-spec-descriptions { max-height: 240px; }
    }
</style>
