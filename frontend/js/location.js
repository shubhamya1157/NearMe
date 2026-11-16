// frontend/js/location.js
// Captures live browser coordinates and reverse-geocodes into the address field

document.addEventListener('DOMContentLoaded', function () {
    const btnGetLocation = document.getElementById('btnGetLocation');
    const inputLat = document.getElementById('latitude');
    const inputLon = document.getElementById('longitude');
    const inputAddress = document.getElementById('address');
    const statusText = document.getElementById('locationStatus');

    if (btnGetLocation) {
        btnGetLocation.addEventListener('click', function () {
            if (!navigator.geolocation) {
                if (statusText) {
                    statusText.textContent = 'Geolocation is not supported by your browser. Please enter coordinates manually.';
                    statusText.style.color = '#dc2626';
                    statusText.style.display = 'block';
                }
                return;
            }

            if (statusText) {
                statusText.textContent = 'Getting your location from browser...';
                statusText.style.color = '#4f46e5';
                statusText.style.display = 'block';
            }
            btnGetLocation.disabled = true;

            navigator.geolocation.getCurrentPosition(
                function (position) {
                    const lat = position.coords.latitude.toFixed(6);
                    const lon = position.coords.longitude.toFixed(6);

                    if (inputLat) inputLat.value = lat;
                    if (inputLon) inputLon.value = lon;

                    if (statusText) {
                        statusText.textContent = 'Location detected. Fetching address...';
                        statusText.style.color = '#16a34a';
                    }
                    btnGetLocation.disabled = false;

                    // Reverse geocode to get city / locality using OpenStreetMap Nominatim
                    fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}`)
                        .then(res => res.json())
                        .then(data => {
                            if (data && data.display_name && inputAddress) {
                                inputAddress.value = data.display_name;
                                if (statusText) {
                                    statusText.textContent = `Location and address detected successfully.`;
                                    statusText.style.color = '#16a34a';
                                }
                            }
                        })
                        .catch(() => {
                            if (statusText) {
                                statusText.textContent = `Coordinates set (${lat}, ${lon}).`;
                                statusText.style.color = '#16a34a';
                            }
                        });
                },
                function (error) {
                    btnGetLocation.disabled = false;
                    let msg = 'Unable to retrieve your location.';
                    if (error.code === error.PERMISSION_DENIED) {
                        msg = 'Location permission was denied. Please allow location access or type coordinates manually.';
                    } else if (error.code === error.POSITION_UNAVAILABLE) {
                        msg = 'Location information is unavailable.';
                    } else if (error.code === error.TIMEOUT) {
                        msg = 'Location request timed out.';
                    }
                    if (statusText) {
                        statusText.textContent = msg;
                        statusText.style.color = '#dc2626';
                        statusText.style.display = 'block';
                    }
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }
            );
        });
    }
});
