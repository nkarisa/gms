<script>
    $(document).ready(function () {

        $(document).on('click', 'a.upload', function () {
            alert($(this).attr('data-target'));
        })

    })
</script>