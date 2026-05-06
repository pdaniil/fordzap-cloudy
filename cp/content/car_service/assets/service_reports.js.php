<script>
    function loadReports() {
        setCookie('time_to_records', document.querySelector('#time_to').value, {'max-age': 3600});
        setCookie('time_from_records', document.querySelector('#time_from').value, {'max-age': 3600});
        location = location;
    }

    function setCookie(name, value, options = {}) {

        options = {
            path: '/',
            // при необходимости добавьте другие значения по умолчанию
            ...options
        };

        if (options.expires instanceof Date) {
            options.expires = options.expires.toUTCString();
        }

        let updatedCookie = encodeURIComponent(name) + "=" + encodeURIComponent(value);

        for (let optionKey in options) {
            updatedCookie += "; " + optionKey;
            let optionValue = options[optionKey];
            if (optionValue !== true) {
            updatedCookie += "=" + optionValue;
        }
    }

        document.cookie = updatedCookie;
    }


</script>

