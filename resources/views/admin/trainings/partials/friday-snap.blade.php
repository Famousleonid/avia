{{-- Training dates are ALWAYS the Friday of the picked week (never future):
     any picked date snaps to its week's Friday; if that Friday hasn't come
     yet — the previous one. Mirrors TrainingController::fridayOf(). --}}
<script>
(function () {
    var SEL = [
        '#date_training',
        '#additional_training_date',
        'input[name="training_dates[]"]',
        '.add-training-date-input',
        '.edit-training-date-input',
        '#mainsUpdateTrainingDateInput',
        '#mains_date_training',
        '#mains_additional_training_date',
    ].join(',');

    function fmt(d) {
        return d.getFullYear() + '-'
            + String(d.getMonth() + 1).padStart(2, '0') + '-'
            + String(d.getDate()).padStart(2, '0');
    }

    function fridayOf(value) {
        var d = new Date(value + 'T00:00:00');
        if (isNaN(d)) return null;
        var dow = (d.getDay() + 6) % 7;          // Mon=0 … Sun=6
        d.setDate(d.getDate() - dow + 4);        // Friday of that week
        var today = new Date(); today.setHours(0, 0, 0, 0);
        if (d > today) d.setDate(d.getDate() - 7); // never future
        return fmt(d);
    }

    function todayStr() {
        var t = new Date(); t.setHours(0, 0, 0, 0);
        return fmt(t);
    }

    document.addEventListener('focusin', function (e) {
        if (e.target.matches && e.target.matches(SEL) && e.target.type === 'date' && !e.target.max) {
            e.target.max = todayStr();
        }
    });

    document.addEventListener('change', function (e) {
        var inp = e.target;
        if (!inp.matches || !inp.matches(SEL) || inp.type !== 'date' || !inp.value) return;
        var friday = fridayOf(inp.value);
        if (friday && inp.value !== friday) {
            inp.value = friday;
            inp.title = 'Adjusted to the training Friday of the selected week';
        }
    });
})();
</script>
