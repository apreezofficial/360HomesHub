<?php
// /utils/geo.php

/**
 * Calculates the distance between two points on Earth given their latitudes and longitudes.
 *
 * @param float $lat1 Latitude of the first point.
 * @param float $lon1 Longitude of the first point.
 * @param float $lat2 Latitude of the second point.
 * @param float $lon2 Longitude of the second point.
 * @return float The distance between the two points in miles.
 */
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    if (($lat1 == $lat2) && ($lon1 == $lon2)) {
        return 0;
    }

    $earthRadius = 3959; // Radius of the Earth in miles

    $latFrom = deg2rad($lat1);
    $lonFrom = deg2rad($lon1);
    $latTo = deg2rad($lat2);
    $lonTo = deg2rad($lon2);

    $latDelta = $latTo - $latFrom;
    $lonDelta = $lonTo - $lonFrom;

    $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
        cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

    return $angle * $earthRadius;
}
