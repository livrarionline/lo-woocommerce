<script type="text/javascript">
    let js_file = document.createElement('script');
    js_file.type = 'text/javascript';
    js_file.src = 'https://maps.googleapis.com/maps/api/js?key=<?php echo $gmaps_api_key?>&language=en';
    js_file.async = true;
    js_file.defer = true;
    document.getElementsByTagName('head')[0].appendChild(js_file);
</script>
