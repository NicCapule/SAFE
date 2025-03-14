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
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/stylist.css">
    <style>
        /* Ensure the map has a defined height and width */
        #map {
            height: 500px;
            width: 100%;
            margin-bottom: 20px;
            border-radius: var(--border-radius);
        }

        /* Style for the radius slider and button */
        .controls {
            margin: 20px 0;
            display: flex;
            gap: 10px;
            align-items: center;
        }

        /* Style for the route info display */
        #route-info {
            margin: 10px 0;
            padding: var(--padding);
            background-color: var(--secondary-color);
            border-radius: var(--border-radius);
            color: var(--text-color);
        }

        /* Style for the station list */
        .station-list {
            margin-top: 15px;
            background-color: rgba(255, 255, 255, 0.1);
            padding: var(--padding);
            border-radius: var(--border-radius);
            max-height: 200px;
            overflow-y: auto;
        }

        .station-item {
            padding: 10px;
            margin-bottom: 10px;
            border-bottom: 1px solid var(--primary-color);
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .station-item:hover {
            background-color: var(--primary-color);
            color: var(--text-color);
        }

        .station-name {
            font-weight: bold;
        }

        .station-info {
            color: var(--accent-color);
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Welcome Section -->
        <div class="dashboard-section welcome-section">
            <h2>Welcome, <?php echo $_SESSION['user_name']; ?>!</h2>
        </div>

        <!-- Map Section -->
        <div class="dashboard-section map-section">
            <div id="map"></div>
            <div class="controls">
                <p>Adjust Search Radius: 
                    <input type="range" id="radiusSlider" min="0.5" max="5" step="0.1" value="2" 
                           oninput="updateRadius(this.value)">
                    <span id="radiusValue">2 km</span>
                </p>
                <button onclick="searchFireStations()">Show Nearest Fire Station Route</button>
            </div>
            <div id="route-info"></div>
            <div id="stations-list" class="station-list" style="display: none;"></div>
        </div>

        <!-- Request Help Section -->
        <div class="dashboard-section request-help-section">
            <h3>Request Help</h3>
            <?php 
            if ($system_status === "Busy") {
                echo "<p class='system-busy'>🚫 The system is currently busy. You cannot submit requests at this time.</p>";
            }
            echo $message;
            ?>
            <form method="POST" id="helpForm">
                <select name="help_type" required>
                    <option value="Fire">Fire</option>
                    <option value="Rescue">Rescue</option>
                    <option value="Other">Other</option>
                </select>
                <input type="text" name="nearest_station" id="nearest_station" placeholder="Nearest Station" readonly>
                <input type="hidden" name="user_lat" id="user_lat">
                <input type="hidden" name="user_lng" id="user_lng">
                <button type="submit" <?php echo ($system_status === 'Busy') ? 'disabled' : ''; ?>>Submit Request</button>
            </form>
        </div>

        <!-- Logout Section -->
        <div class="dashboard-section logout-section">
            <a href="../auth/logout.php">Logout</a>
        </div>
    </div>

    <?php if ($show_popup) { ?>
    <script>
        alert("✅ Request sent successfully!");
    </script>
    <?php } ?>

    <script>
        // Your existing JavaScript code here
        let map, userMarker, radiusCircle, directionsService, directionsRenderer;
        let radius = 2000;
        let fireStationMarkers = [];
        let userLocation = null;
        let allFireStations = [];
        let searchTimeout;

        function initMap() {
            directionsService = new google.maps.DirectionsService();
            directionsRenderer = new google.maps.DirectionsRenderer({
                suppressMarkers: false,
                preserveViewport: true
            });
            
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
                
                directionsRenderer.setMap(map);

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
                
                // Only display fire stations on page load, not the route
                displayNearbyFireStations();
                
                // Form submission event handler
                document.getElementById('helpForm').addEventListener('submit', function(e) {
                    if (!document.getElementById('nearest_station').value) {
                        e.preventDefault();
                        alert("Please wait while we find the nearest fire station.");
                        searchFireStations();
                        return false;
                    }
                });
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
            
            // Add debounce to avoid too many API calls
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(displayNearbyFireStations, 500);
        }

        // New function that only displays fire stations without calculating routes
        function displayNearbyFireStations() {
            if (!userLocation) {
                alert("User location not found. Try again.");
                return;
            }

            clearFireStationMarkers();
            allFireStations = [];
            
            const request = {
                location: userLocation,
                radius: radius,
                type: 'fire_station'
            };

            const service = new google.maps.places.PlacesService(map);
            service.nearbySearch(request, (results, status) => {
                if (status === google.maps.places.PlacesServiceStatus.OK && results.length > 0) {
                    displayStationsList(results);
                    addFireStationMarkers(results);
                    document.getElementById('route-info').innerHTML = 'Click "Search Nearby Fire Stations" to find the fastest route.';
                } else {
                    document.getElementById('route-info').innerHTML = 'No fire stations found within the selected radius.';
                    document.getElementById('stations-list').style.display = 'none';
                    document.getElementById('nearest_station').value = '';
                }
            });
        }

        // Function to add markers for fire stations without calculating routes
        function addFireStationMarkers(stations) {
            stations.forEach(station => {
                const marker = new google.maps.Marker({
                    position: station.geometry.location,
                    map: map,
                    title: station.name,
                    icon: {
                        url: 'http://maps.google.com/mapfiles/ms/icons/red-dot.png'
                    }
                });
                
                fireStationMarkers.push(marker);
            });
        }

        // Original search function - now only called when button is clicked
        function searchFireStations() {
            if (!userLocation) {
                alert("User location not found. Try again.");
                return;
            }

            // Don't clear markers, just update routes
            allFireStations = [];
            
            const request = {
                location: userLocation,
                radius: radius,
                type: 'fire_station'
            };

            const service = new google.maps.places.PlacesService(map);
            service.nearbySearch(request, (results, status) => {
                if (status === google.maps.places.PlacesServiceStatus.OK && results.length > 0) {
                    // Update the list in case the radius changed
                    displayStationsList(results);
                    calculateRoutesToStations(results);
                } else {
                    document.getElementById('route-info').innerHTML = 'No fire stations found within the selected radius.';
                    document.getElementById('stations-list').style.display = 'none';
                    document.getElementById('nearest_station').value = '';
                }
            });
        }

        function displayStationsList(stations) {
            const listElement = document.getElementById('stations-list');
            listElement.innerHTML = '<h4>Fire Stations in Range</h4>';
            listElement.style.display = 'block';
            
            stations.forEach((station, index) => {
                const stationElement = document.createElement('div');
                stationElement.className = 'station-item';
                stationElement.innerHTML = `
                    <div class="station-name">${station.name}</div>
                    <div class="station-info">${station.vicinity}</div>
                `;
                stationElement.onclick = function() {
                    showRouteToStation(station);
                };
                listElement.appendChild(stationElement);
                
                allFireStations.push({
                    station: station,
                    element: stationElement
                });
            });
        }

        function calculateRoutesToStations(stations) {
            let requests = [];
            let nearestStation = null;
            let minDuration = Infinity;
            
            // Calculate route from station to user
            stations.forEach(station => {
                const routeRequest = {
                    origin: station.geometry.location,
                    destination: userLocation,
                    travelMode: 'DRIVING'
                };
                
                requests.push(new Promise((resolve) => {
                    directionsService.route(routeRequest, (result, status) => {
                        if (status === 'OK') {
                            const duration = result.routes[0].legs[0].duration.value;
                            const stationInfo = {
                                station: station,
                                route: result,
                                duration: duration,
                                distance: result.routes[0].legs[0].distance.text,
                                durationText: result.routes[0].legs[0].duration.text
                            };
                            
                            if (duration < minDuration) {
                                minDuration = duration;
                                nearestStation = stationInfo;
                            }
                            
                            resolve(stationInfo);
                        } else {
                            resolve(null);
                        }
                    });
                }));
            });
            
            // Wait for all route calculations to complete
            Promise.all(requests).then(results => {
                results = results.filter(r => r !== null);
                
                if (results.length > 0 && nearestStation) {
                    // Display route to nearest station
                    directionsRenderer.setDirections(nearestStation.route);
                    document.getElementById('route-info').innerHTML = `
                        <strong>Fastest Route from ${nearestStation.station.name} to You</strong><br>
                        Distance: ${nearestStation.distance}<br>
                        Estimated time: ${nearestStation.durationText}<br>
                        <em>Click on any station in the list below to see alternative routes</em>
                    `;
                    
                    // Highlight the nearest station in the list
                    allFireStations.forEach(item => {
                        if (item.station.place_id === nearestStation.station.place_id) {
                            item.element.style.backgroundColor = '#ffe0e0';
                            item.element.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                        }
                    });
                    
                    // Set the nearest station for the form
                    document.getElementById('nearest_station').value = nearestStation.station.name;
                } else {
                    document.getElementById('nearest_station').value = '';
                }
            });
        }

        function showRouteToStation(station) {
            const routeRequest = {
                origin: station.geometry.location,
                destination: userLocation,
                travelMode: 'DRIVING'
            };
            
            directionsService.route(routeRequest, (result, status) => {
                if (status === 'OK') {
                    directionsRenderer.setDirections(result);
                    document.getElementById('route-info').innerHTML = `
                        <strong>Route from ${station.name} to You</strong><br>
                        Distance: ${result.routes[0].legs[0].distance.text}<br>
                        Estimated time: ${result.routes[0].legs[0].duration.text}
                    `;
                    
                    // Update the nearest station field when a station is selected
                    document.getElementById('nearest_station').value = station.name;
                }
            });
        }

        function clearFireStationMarkers() {
            fireStationMarkers.forEach(marker => marker.setMap(null));
            fireStationMarkers = [];
            directionsRenderer.setDirections({routes: []});
        }

        window.onload = initMap;
    </script>
    <script async defer
        src="https://maps.googleapis.com/maps/api/js?key=<?php echo $google_api_key; ?>&libraries=places,geometry">
    </script>
</body>
</html>