<?php

/*
 * This file is part of the Active Collab DatabaseConnection project.
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseConnection\Spatial\WktParser;

use ActiveCollab\DatabaseConnection\Spatial\Point\Point;
use ActiveCollab\DatabaseConnection\Spatial\Point\PointInterface;
use ActiveCollab\DatabaseConnection\Spatial\Coordinate\Coordinate;
use ActiveCollab\DatabaseConnection\Spatial\LinearRing\LinearRing;
use ActiveCollab\DatabaseConnection\Spatial\LinearRing\LinearRingInterface;
use ActiveCollab\DatabaseConnection\Spatial\Polygon\Polygon;
use ActiveCollab\DatabaseConnection\Spatial\Polygon\PolygonInterface;
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
        $lowered_text = strtolower($text);
        $type_pattern = '/\s*(\w+)\s*\(\s*(.*)\s*\)\s*$/';
        if (!preg_match($type_pattern, $lowered_text, $matches)) {
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
            $components = call_user_func(
                [
                    $this,
                    sprintf('parse%s', $type),
                ],
                $matches[2]
            );
        } catch (Exception $e) {
            throw new InvalidWktException($text, $e);
        }

        if (in_array($type, [self::POINT, self::POLYGON])) {
            return $components;
        }

        $constructor = __NAMESPACE__ . '\\' . $type;
        return new $constructor($components);
    }

    private function parsePoint(string $text): PointInterface
    {
        $parsed_text = preg_split('/\s+/', trim($text));

        return new Point(
            new Coordinate((float) $parsed_text[0]),
            new Coordinate((float) $parsed_text[1]),
        );
    }

    protected function parseMultiPoint(string $str)
    {
        $str = trim($str);

        if (strlen ($str) == 0) {
            return [];
        }

        return $this->parseLineString($str);
    }

    /**
     * @return PointInterface[]
     */
    protected function parseLineString(string $text): array
    {
        $components = [];

        foreach (preg_split('/,/', trim($text)) as $point_text) {
            $components[] = $this->parsePoint($point_text);
        }

        return $components;
    }

    private function parseLinearRing(string $text): LinearRingInterface
    {
        return new LinearRing(...$this->parseLineString($text));
    }

    private function parsePolygon(string $text): PolygonInterface
    {
        $boundaries = $this->_parseCollection($text, self::LINEAR_RING);
        $exterior_boundary = array_pop($boundaries);

        return new Polygon($exterior_boundary, ...$boundaries);
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

    protected function _parseCollection(string $text, $child_constructor) {
        $components = [];

        foreach (preg_split('/\)\s*,\s*\(/', trim($text)) as $parse_text) {
            if (strlen($parse_text) and $parse_text[0] == '(') {
                $parse_text = substr($parse_text, 1);
            }

            if (strlen($parse_text) and $parse_text[strlen($parse_text)-1] == ')') {
                $parse_text = substr($parse_text, 0, -1);
            }

            $components[] = call_user_func(
                [
                    $this,
                    sprintf('parse%s', $child_constructor)
                ],
                $parse_text
            );
        }

        return $components;
    }
}