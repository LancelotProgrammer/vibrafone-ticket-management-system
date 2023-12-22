<div class="container-fluid py-5 wow fadeInUp" data-wow-delay="0.1s">
    <div class="container py-5">
        <div class="section-title text-center position-relative pb-3 mb-5 mx-auto" style="max-width: 600px;">
            <h4><a class="download" href="{{ route('tickets.files.download', $id) }}">Click here, if your download does
                    not start automatically.</a></h4>
            <script>
                window.onload = function() {
                    document.getElementsByClassName('download')[0].click();
                }
            </script>
        </div>
    </div>
</div>
