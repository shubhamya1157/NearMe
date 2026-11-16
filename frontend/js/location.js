// frontend/js/location.js
// Handles browser geolocation detection, preset coordinates, and address reverse geocoding

document.addEventListener('DOMContentLoaded', function () {
    const btnDetect = document.getElementById('btnDetectLocation');
    const inputLat = document.getElementById('latitude');
    const inputLon = document.getElementById('longitude');
    const inputAddress = document.getElementById('address');
    const statusText = document.getElementById('locationStatus');

    // Preset location data
    const presets = {
        kota: {
            name: 'Kota (Demo Center)',
            lat: 25.18000000,
            lon: 75.83000000,
            address: 'Station Road, Nayapura, Kota, Rajasthan'
        },
        jaipur: {
            name: 'Jaipur',
            lat: 26.91240000,
            lon: 75.78730000,
            address: 'MI Road, Jaipur, Rajasthan'
        },
        delhi: {
            name: 'Delhi NCR',
            lat: 28.61390000,
            lon: 77.20900000,
            address: 'Connaught Place, New Delhi'
        },
        mumbai: {
            name: 'Mumbai',
            lat: 19.07600000,
            lon: 72.87770000,
            address: 'Bandra West, Mumbai, Maharashtra'
        },
        bangalore: {
            name: 'Bangalore',
            lat: 12.97160000,
            lon: 77.59460000,
            address: 'Koramangala, Bengaluru, Karnataka'
        }
    };

    // Auto-detect button handler
    if (btnDetect) {
        btnDetect.addEventListener('click', function () {
            if (!navigator.geolocation) {
                if (statusText) {
                    statusText.textContent = '❌ Geolocation is not supported by your browser. Please enter coordinates manually or use a preset below.';
                    statusText.style.color = '#ef4444';
                }
                return;
            }

            if (statusText) {
                statusText.textContent = '🛰️ Requesting your location from browser...';
                statusText.style.color = '#4f46e5';
            }
            btnDetect.disabled = true;

            navigator.geolocation.getCurrentPosition(
                function (position) {
                    const lat = position.coords.latitude.toFixed(8);
                    const lon = position.coords.longitude.toFixed(8);

                    if (inputLat) inputLat.value = lat;
                    if (inputLon) inputLon.value = lon;

                    if (statusText) {
                        statusText.textContent = `✅ Location captured: ${lat}, ${lon}. Finding address...`;
                        statusText.style.color = '#10b981';
                    }
                    btnDetect.disabled = false;

                    // Try reverse geocoding via OpenStreetMap Nominatim
                    fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}`)
                        .then(res => res.json())
                        .then(data => {
                            if (data && data.display_name && inputAddress && (!inputAddress.value || inputAddress.value.trim() === '')) {
                                inputAddress.value = data.display_name;
                                if (statusText) {
                                    statusText.textContent = `✅ Location captured & address autofilled!`;
                                }
                            }
                        })
                        .catch(() => {
                            // Ignore Nominatim rate-limit or offline errors gracefully
                            if (statusText) {
                                statusText.textContent = `✅ Coordinates set: ${lat}, ${lon}. (Enter address if needed)`;
                            }
                        });
                },
                function (err) {
                    btnDetect.disabled = false;
                    let msg = 'Failed to get location. ';
                    switch (err.code) {
                        case err.PERMISSION_DENIED:
                            msg += 'Permission was denied. Please allow location access or choose a preset.';
                            break;
                        case err.POSITION_UNAVAILABLE:
                            msg += 'Position unavailable. Check your device GPS.';
                            break;
                        case err.TIMEOUT:
                            msg += 'Request timed out.';
                            break;
                        default:
                            msg += err.message;
                    }
                    if (statusText) {
                        statusText.textContent = `⚠️ ${msg}`;
                        statusText.style.color = '#f59e0b';
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

    // Attach click events to preset buttons
    const presetButtons = document.querySelectorAll('.preset-btn');
    presetButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            const cityKey = this.getAttribute('data-preset');
            const data = presets[cityKey];
            if (data) {
                if (inputLat) inputLat.value = data.lat.toFixed(8);
                if (inputLon) inputLon.value = data.lon.toFixed(8);
                if (inputAddress && (!inputAddress.value || inputAddress.value.trim() === '')) {
                    inputAddress.value = data.address;
                }
                if (statusText) {
                    statusText.textContent = `📍 Selected preset: ${data.name} (${data.lat}, ${data.lon})`;
                    statusText.style.color = '#4f46e5';
                }
            }
        });
    });
});
