<?php
// this is only the map. No other feature
$google_api_key = 'AIzaSyAuVXA62gxQgGtLg7jEf0hQnFgYHzxJO48'; 
?>

<!DOCTYPE html>
<html>
<head>
    <title>Makati Services Finder</title>
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
        .location-type {
            padding: 8px 16px;
            cursor: pointer;
            background-color: #4285F4;
            color: white;
            border: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        .location-type:hover {
            background-color: #3267d6;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            border-radius: 8px;
        }
        .close-button {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close-button:hover {
            color: black;
        }
        .location-item {
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .location-item:hover {
            background-color: #f5f5f5;
        }
        #route-info {
            margin: 10px 0;
            padding: 10px;
            background-color: #f0f0f0;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="controls">
        <button class="location-type" onclick="searchNearby('hospital')">Find Hospitals</button>
        <button class="location-type" onclick="searchNearby('police')">Find Police Stations</button>
    </div>
    <div id="route-info"></div>
    <div id="map"></div>

    <!-- Modal -->
    <div id="locationModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeModal()">&times;</span>
            <h2 id="modal-title">Locations in Makati</h2>
            <div id="location-list"></div>
        </div>
    </div>

    <script>
        let map;
        let service;
        let infowindow;
        let markers = [];
        let currentPosition;
        let directionsService;
        let directionsRenderer;
        let cityCircle;
        let geocoder;

        function initMap() {
            geocoder = new google.maps.Geocoder();
            
            // Makati City coordinates
            const makatiCenter = { lat: 14.5547, lng: 121.0244 };
            
            map = new google.maps.Map(document.getElementById('map'), {
                center: makatiCenter,
                zoom: 14,
                styles: [
                    {
                        featureType: "administrative.locality",
                        elementType: "geometry",
                        stylers: [{ visibility: "on" }]
                    }
                ]
            });

            infowindow = new google.maps.InfoWindow();
            service = new google.maps.places.PlacesService(map);
            directionsService = new google.maps.DirectionsService();
            
            // Initialize directionsRenderer
            initializeDirectionsRenderer();

            // Set Makati City circular boundary
            setMakatiCircle(makatiCenter);

            // Set current position to Makati center by default
            currentPosition = makatiCenter;

            // Try to get user's location
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const userPosition = {
                            lat: position.coords.latitude,
                            lng: position.coords.longitude
                        };

                        // Check if user is within or near Makati
                        const makatiProximity = new google.maps.LatLng(14.5547, 121.0244);
                        const userLocation = new google.maps.LatLng(userPosition.lat, userPosition.lng);
                        const distance = google.maps.geometry.spherical.computeDistanceBetween(makatiProximity, userLocation);

                        if (distance <= 5000) {
                            currentPosition = userPosition;
                            // Add marker for user location
                            new google.maps.Marker({
                                position: currentPosition,
                                map: map,
                                icon: {
                                    path: google.maps.SymbolPath.CIRCLE,
                                    scale: 10,
                                    fillColor: '#4285F4',
                                    fillOpacity: 1,
                                    strokeColor: 'white',
                                    strokeWeight: 2,
                                },
                                title: 'Your Location'
                            });
                        }
                    },
                    (error) => {
                        console.error('Error getting location:', error);
                    }
                );
            }
        }

        function initializeDirectionsRenderer() {
            // Remove existing directionsRenderer if it exists
            if (directionsRenderer) {
                directionsRenderer.setMap(null);
            }
            
            // Create new directionsRenderer
            directionsRenderer = new google.maps.DirectionsRenderer({
                map: map,
                suppressMarkers: true
            });
        }

        function setMakatiCircle(center) {
            if (cityCircle) {
                cityCircle.setMap(null);
            }

            cityCircle = new google.maps.Circle({
                strokeColor: '#FF0000',
                strokeOpacity: 0.8,
                strokeWeight: 2,
                fillColor: '#FF0000',
                fillOpacity: 0.1,
                map: map,
                center: center,
                radius: 3000  // 3km radius to approximate Makati's area
            });

            map.fitBounds(cityCircle.getBounds());
        }

        function calculateRoute(destination, locationName) {
            // Clear existing route
            initializeDirectionsRenderer();

            const request = {
                origin: currentPosition,
                destination: destination,
                travelMode: 'DRIVING'
            };

            directionsService.route(request, (result, status) => {
                if (status === 'OK') {
                    directionsRenderer.setDirections(result);

                    const route = result.routes[0].legs[0];
                    const routeInfo = document.getElementById('route-info');
                    routeInfo.innerHTML = `
                        <strong>Route to ${locationName}</strong><br>
                        Distance: ${route.distance.text}<br>
                        Estimated time: ${route.duration.text}<br>
                        Start: ${route.start_address}<br>
                        End: ${route.end_address}
                    `;
                    closeModal();
                }
            });
        }

        function searchNearby(type) {
            clearMarkers();
            initializeDirectionsRenderer();
            document.getElementById('route-info').innerHTML = '';
            
            const request = {
                location: currentPosition,
                radius: 5000,
                type: type
            };

            // Update modal title based on search type
            document.getElementById('modal-title').textContent = 
                type === 'hospital' ? 'Hospitals in Makati' : 'Police Stations in Makati';

            service.nearbySearch(request, (results, status) => {
                if (status === google.maps.places.PlacesServiceStatus.OK) {
                    // Filter results to only show places within Makati circle
                    const makatiResults = results.filter(place => {
                        return google.maps.geometry.spherical.computeDistanceBetween(
                            new google.maps.LatLng(place.geometry.location.lat(), place.geometry.location.lng()),
                            new google.maps.LatLng(cityCircle.getCenter().lat(), cityCircle.getCenter().lng())
                        ) <= cityCircle.getRadius();
                    });
                    
                    displayResults(makatiResults, type);
                    showModal();
                }
            });
        }

        function displayResults(places, type) {
            const locationList = document.getElementById('location-list');
            locationList.innerHTML = '';
            
            places.forEach((place, i) => {
                const distance = google.maps.geometry.spherical.computeDistanceBetween(
                    new google.maps.LatLng(currentPosition),
                    place.geometry.location
                );
                const distanceInKm = (distance / 1000).toFixed(2);

                // Set marker icon based on type
                const markerIcon = type === 'hospital' ? 
                    'http://maps.google.com/mapfiles/ms/icons/red-dot.png' :
                    'http://maps.google.com/mapfiles/ms/icons/police.png';

                const marker = new google.maps.Marker({
                    position: place.geometry.location,
                    map: map,
                    title: place.name,
                    icon: markerIcon
                });
                
                markers.push(marker);

                marker.addListener('click', () => {
                    infowindow.setContent(
                        `<div>
                            <strong>${place.name}</strong><br>
                            ${place.vicinity}<br>
                            Distance: ${distanceInKm} km<br>
                            Rating: ${place.rating || 'N/A'}<br>
                            ${place.opening_hours?.open_now ? 'Open Now' : 'Closed/Unknown'}<br>
                            <button onclick="calculateRoute(
                                {lat: ${place.geometry.location.lat()}, lng: ${place.geometry.location.lng()}},
                                '${place.name.replace(/'/g, "\\'")}'
                            )">Show Route</button>
                        </div>`
                    );
                    infowindow.open(map, marker);
                });

                const locationItem = document.createElement('div');
                locationItem.className = 'location-item';
                locationItem.innerHTML = `
                    <strong>${i + 1}. ${place.name}</strong><br>
                    Address: ${place.vicinity}<br>
                    Distance: ${distanceInKm} km<br>
                    Rating: ${place.rating || 'N/A'}<br>
                    ${place.opening_hours?.open_now ? 'Open Now' : 'Closed/Unknown'}<br>
                    <small>(Click to show route)</small>
                `;
                locationItem.onclick = () => calculateRoute(
                    {lat: place.geometry.location.lat(), lng: place.geometry.location.lng()},
                    place.name
                );
                locationList.appendChild(locationItem);
            });
        }

        function clearMarkers() {
            markers.forEach(marker => marker.setMap(null));
            markers = [];
        }

        function showModal() {
            document.getElementById('locationModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('locationModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('locationModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
    <script async defer
        src="https://maps.googleapis.com/maps/api/js?key=<?php echo $google_api_key; ?>&libraries=places,geometry,drawing&callback=initMap">
    </script>
</body>
</html>
