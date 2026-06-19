@if(old('_native_test_modal'))
<script>
document.addEventListener('DOMContentLoaded', function () {
    var modalId = @json(old('_native_test_modal'));
    var modalElement = document.getElementById(modalId);
    if (modalElement && window.bootstrap && window.bootstrap.Modal) {
        window.bootstrap.Modal.getOrCreateInstance(modalElement).show();
    }
});
</script>
@endif
