<?php
session_start();
require '../config/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['is_admin']) {
    header("Location: ../auth/login.php");
    exit;
}

$google_api_key = 'AIzaSyAuVXA62gxQgGtLg7jEf0hQnFgYHzxJO48';

// Fetch system status
$status_result = $conn->query("SELECT status FROM system_status WHERE id = 1");
$status_row = $status_result->fetch_assoc();
$system_status = $status_row ? $status_row['status'] : 'Unknown';

// Handle help request submission
$message = "";
$show_popup = false;
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['help_type'])) {
    if ($system_status === "Busy") {
        $message = "<p style='color: red;'>🚫 System is busy. Cannot submit request.</p>";
    } else {
        $user = $_SESSION['user_name'];
        $help_type = $_POST['help_type'];
        $user_lat = $_POST['user_lat'];
        $user_lng = $_POST['user_lng'];
        $station_name = $_POST['nearest_station'];

        if (!empty($user) && !empty($help_type) && !empty($user_lat) && !empty($user_lng) && !empty($station_name)) {
            $stmt = $conn->prepare("INSERT INTO requests (user, help_type, status, station_name, user_lat, user_lng) VALUES (?, ?, 'Pending', ?, ?, ?)");
            $stmt->bind_param("sssss", $user, $help_type, $station_name, $user_lat, $user_lng);
            if ($stmt->execute()) {
                $show_popup = true; 
            } else {
                $message = "<p style='color: red;'>❌ Error submitting request.</p>";
            }
            $stmt->close();
        } else {
            $message = "<p style='color: red;'>⚠ All fields must be filled out.</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>User Dashboard</title>
        <style>
            #map {
                height: 500px;
                width: 100%;
                margin-bottom: 20px;
            }
            .controls {
                margin: 20px 0;
                display: flex;
                gap: 10px;
            }
            #route-info {
                margin: 10px 0;
                padding: 10px;
                background-color: #f0f0f0;
                border-radius: 5px;
            }
            body {
                background: rgb(128,0,32);
            }
        </style>
</head>
<body onload="initMap()">
    <style>
        h2 {
            color: white;
            font-family:verdana;
        }
        h3 {
            color: white;
            font-family:verdana;
        }
        p {
            color: white;
            font-family:verdana;
        }
        a {
            color: white;
            font-family:verdana;
        }
    </style>
    <h2>Welcome, <?php echo $_SESSION['user_name']; ?>!</h2>

    <div id="map"></div>
    <p>Adjust Search Radius: 
        <input type="range" id="radiusSlider" min="0.5" max="5" step="0.1" value="2" 
               oninput="updateRadius(this.value)">
        <span id="radiusValue">2 km</span>
    </p>
    <button onclick="searchFireStations()">Search for Nearby Fire Stations</button>
    <div id="route-info"></div>

    <h3>Request Help</h3>
    <?php 
    if ($system_status === "Busy") {
        echo "<p class='system-busy'>🚫 The system is currently busy. You cannot submit requests at this time.</p>";
    }
    echo $message;
    ?>
    <form method="POST">
        <select name="help_type" required>
            <option value="Fire">Fire</option>
            <option value="Rescue">Rescue</option>
            <option value="Other">Other</option>
        </select>
        <input type="hidden" name="user_lat" id="user_lat">
        <input type="hidden" name="user_lng" id="user_lng">
        <input type="text" name="nearest_station" id="nearest_station" placeholder="Nearest Station" readonly>
        <button type="submit" <?php echo ($system_status === 'Busy') ? 'disabled' : ''; ?>>Submit Request</button>
    </form>

    <br>
    <a href="../auth/logout.php">Logout</a>

    <?php if ($show_popup) { ?>
    <script>
        alert("✅ Request sent successfully!");
    </script>
    <?php } ?>

    <script>
        let map, userMarker, radiusCircle, directionsService, directionsRenderer;
        let radius = 2000;
        let fireStationMarkers = [];
        let userLocation = null;
        
        function initMap() {
            navigator.geolocation.getCurrentPosition(position => {
                userLocation = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude
                };

                document.getElementById('user_lat').value = userLocation.lat;
                document.getElementById('user_lng').value = userLocation.lng;

                map = new google.maps.Map(document.getElementById('map'), {
                    center: userLocation,
                    zoom: 14
                });

                userMarker = new google.maps.Marker({
                    position: userLocation,
                    map: map,
                    title: "Your Location",
                    icon: {
                        path: google.maps.SymbolPath.CIRCLE,
                        scale: 8,
                        fillColor: "#4285F4",
                        fillOpacity: 1,
                        strokeWeight: 2,
                        strokeColor: "#ffffff"
                    }
                });

                radiusCircle = new google.maps.Circle({
                    strokeColor: "#FF0000",
                    strokeOpacity: 0.8,
                    strokeWeight: 2,
                    fillColor: "#FF0000",
                    fillOpacity: 0.35,
                    map: map,
                    center: userLocation,
                    radius: radius
                });

                directionsService = new google.maps.DirectionsService();
                directionsRenderer = new google.maps.DirectionsRenderer({ map: map });
            }, error => {
                console.error('Geolocation error:', error);
                alert('Error getting your location. Please enable location services.');
            });
        }

        function updateRadius(value) {
            radius = value * 1000;
            document.getElementById('radiusValue').textContent = value + ' km';
            if (radiusCircle) {
                radiusCircle.setRadius(radius);
            }
        }

        function searchFireStations() {
            if (!userLocation) {
                alert("User location not found. Try again.");
                return;
            }

            clearFireStationMarkers();
            const request = {
                location: userLocation,
                radius: radius,
                type: 'fire_station'
            };

            const service = new google.maps.places.PlacesService(map);
            service.nearbySearch(request, (results, status) => {
                if (status === google.maps.places.PlacesServiceStatus.OK) {
                    let nearestStation = null;
                    let minDuration = Infinity;

                    results.forEach(station => {
                        const stationPos = station.geometry.location;
                        
                        const routeRequest = {
                            origin: stationPos,
                            destination: userLocation,
                            travelMode: 'DRIVING'
                        };

                        directionsService.route(routeRequest, (result, status) => {
                            if (status === 'OK') {
                                const duration = result.routes[0].legs[0].duration.value; // Time in seconds
                                
                                if (duration < minDuration) {
                                    minDuration = duration;
                                    nearestStation = station;
                                    directionsRenderer.setDirections(result);
                                    document.getElementById('route-info').innerHTML = `
                                        <strong>Fastest Route from ${station.name} to You</strong><br>
                                        Distance: ${result.routes[0].legs[0].distance.text}<br>
                                        Estimated time: ${result.routes[0].legs[0].duration.text}
                                    `;
                                    document.getElementById('nearest_station').value = station.name;
                                }
                            }
                        });
                    });
                }
            });
        }

        function clearFireStationMarkers() {
            fireStationMarkers.forEach(marker => marker.setMap(null));
            fireStationMarkers = [];
        }
    </script>
    <script async defer
        src="https://maps.googleapis.com/maps/api/js?key=<?php echo $google_api_key; ?>&libraries=places,geometry&callback=initMap">
    </script>
</body>
</html>
