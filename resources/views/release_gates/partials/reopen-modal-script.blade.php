@if(old('_release_gate_modal'))
<script>
document.addEventListener('DOMContentLoaded', function () {
    var modalId = @json(old('_release_gate_modal'));
    var modalElement = document.getElementById(modalId);
    if (modalElement && window.bootstrap && window.bootstrap.Modal) {
        window.bootstrap.Modal.getOrCreateInstance(modalElement).show();
    }
});
</script>
@endif
