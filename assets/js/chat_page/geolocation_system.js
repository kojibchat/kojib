$(window).on('load', function() {
    if (system_variable('people_nearby_feature') === 'enable') {
        locate_user_position();
    }
});

function locate_user_position() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(add_geo_location);
    } else {
        console.log("Geolocation is not supported by this browser.");
    }
}

function add_geo_location(position) {

    if (position !== undefined) {
        if (position.coords !== undefined) {
            var data = {
                update: "site_user_location",
                latitude: position.coords.latitude,
                longitude: position.coords.longitude
            };

            if (user_csrf_token !== null) {
                data["csrf_token"] = user_csrf_token;
            }

            $.ajax({
                type: 'POST',
                url: api_request_url,
                data: data,
                async: true,
                success: function(data) {}
            });
        }
    }
}