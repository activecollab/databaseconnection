<?php

/*
 * This file is part of the Active Collab DatabaseConnection project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseConnection\Spatial\WktParser;

use ActiveCollab\DatabaseConnection\Spatial\Coordinates\Coordinate;
use ActiveCollab\DatabaseConnection\Spatial\Coordinates\Latitude;
use ActiveCollab\DatabaseConnection\Spatial\Coordinates\Longitude;
use ActiveCollab\DatabaseConnection\Spatial\Polygon;
use Exception;

class WktParser
{
    const POINT = 'Point';
    const MULTI_POINT = 'MultiPoint';
    const LINE_STRING = 'LineString';
    const MULTI_LINE_STRING = 'MultiLinestring';
    const LINEAR_RING = 'LinearRing';
    const POLYGON = 'Polygon';
    const MULTI_POLYGON = 'MultiPolygon';
    const GEOMETRY_COLLECTION = 'GeometryCollection';

    private const WKT_TYPES = [
        self::POINT,
        self::MULTI_POINT,
        self::LINE_STRING,
        self::MULTI_LINE_STRING,
        self::LINEAR_RING,
        self::POLYGON,
        self::MULTI_POLYGON,
        self::GEOMETRY_COLLECTION,
    ];

    public function geomFromText($text) {
        $ltext = strtolower($text);
        $type_pattern = '/\s*(\w+)\s*\(\s*(.*)\s*\)\s*$/';
        if (!preg_match($type_pattern, $ltext, $matches)) {
            throw new InvalidWktException($text);
        }
        foreach (self::WKT_TYPES as $wkt_type) {
            if (strtolower($wkt_type) == $matches[1]) {
                $type = $wkt_type;
                break;
            }
        }

        if (!isset($type)) {
            throw new InvalidWktException($text);
        }

        try {
            $components = call_user_func([$this, 'parse' . $type], $matches[2]);
        } catch (Exception $e) {
            throw new InvalidWktException($text, $e);
        }

        if ($type === self::POLYGON) {
            return new Polygon(...$components);
        }

        $constructor = __NAMESPACE__ . '\\' . $type;
        return new $constructor($components);
    }

    protected function parsePoint($str) {
        return preg_split('/\s+/', trim($str));
    }

    protected function parseMultiPoint($str) {
        $str = trim($str);
        if (strlen ($str) == 0) {
            return [];
        }
        return $this->parseLineString($str);
    }

    protected function parseLineString($str)
    {
        $components = array();
        foreach (preg_split('/,/', trim($str)) as $compstr) {
            $parsed_point = $this->parsePoint($compstr);

            $components[] = new Coordinate(
                new Latitude((float) $parsed_point[0]),
                new Longitude((float) $parsed_point[1]),
            );
        }
        return $components;
    }

    protected function parseLinearRing($str) {
        return $this->parseLineString($str);
    }

    protected function parsePolygon($str) {
        return $this->_parseCollection($str, 'LinearRing');
    }

    protected function parseMultiPolygon($str) {
        return $this->_parseCollection($str, "Polygon");
    }

    protected function parseGeometryCollection($str) {
        $components = [];
        foreach (preg_split('/,\s*(?=[A-Za-z])/', trim($str)) as $compstr) {
            $components[] = $this->geomFromText($compstr);
        }
        return $components;
    }

    protected function _parseCollection($str, $child_constructor) {
        $components = [];
        foreach (preg_split('/\)\s*,\s*\(/', trim($str)) as $compstr) {
            if (strlen($compstr) and $compstr[0] == '(') {
                $compstr = substr($compstr, 1);
            }
            if (strlen($compstr) and $compstr[strlen($compstr)-1] == ')') {
                $compstr = substr($compstr, 0, -1);
            }

            $children = call_user_func([$this, 'parse' . $child_constructor], $compstr);

            var_dump($children);

//            $constructor = __NAMESPACE__ . '\\' . $child_constructor;
//            $components[] = new $constructor($children);
        }
        return $components;
    }
}
