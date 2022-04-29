<?php

/*
 * This file is part of the Active Collab DatabaseConnection project.
 *
 * Based on GisConverter library: https://github.com/arenevier/gisconverter.php
 *
 * (c) A51 doo <info@activecollab.com>. All rights reserved.
 */

declare(strict_types=1);

namespace ActiveCollab\DatabaseConnection\Spatial\WktParser;

use ActiveCollab\DatabaseConnection\Spatial\GeometricObjectInterface;
use ActiveCollab\DatabaseConnection\Spatial\GeometryCollection\GeometryCollection;
use ActiveCollab\DatabaseConnection\Spatial\GeometryCollection\GeometryCollectionInterface;
use ActiveCollab\DatabaseConnection\Spatial\LineString\LineString;
use ActiveCollab\DatabaseConnection\Spatial\LineString\LineStringInterface;
use ActiveCollab\DatabaseConnection\Spatial\MultiLineString\MultiLineString;
use ActiveCollab\DatabaseConnection\Spatial\MultiLineString\MultiLineStringInterface;
use ActiveCollab\DatabaseConnection\Spatial\MultiPoint\MultiPoint;
use ActiveCollab\DatabaseConnection\Spatial\MultiPoint\MultiPointInterface;
use ActiveCollab\DatabaseConnection\Spatial\MultiPolygon\MultiPolygon;
use ActiveCollab\DatabaseConnection\Spatial\MultiPolygon\MultiPolygonInterface;
use ActiveCollab\DatabaseConnection\Spatial\Point\Point;
use ActiveCollab\DatabaseConnection\Spatial\Point\PointInterface;
use ActiveCollab\DatabaseConnection\Spatial\Coordinate\Coordinate;
use ActiveCollab\DatabaseConnection\Spatial\LinearRing\LinearRing;
use ActiveCollab\DatabaseConnection\Spatial\LinearRing\LinearRingInterface;
use ActiveCollab\DatabaseConnection\Spatial\Polygon\Polygon;
use ActiveCollab\DatabaseConnection\Spatial\Polygon\PolygonInterface;
use Exception;
use LogicException;

class WktParser implements WktParserInterface
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

    public function parse(string $text): GeometricObjectInterface
    {
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

        if (!in_array($type, self::WKT_TYPES)) {
            throw new LogicException(sprintf('Unsupported type %s.', $type));
        }

        try {
            return match ($type) {
                self::POINT => $this->parsePoint($matches[2]),
                self::MULTI_POINT => $this->parseMultiPoint($matches[2]),
                self::LINE_STRING => $this->parseLineString($matches[2]),
                self::MULTI_LINE_STRING => $this->parseMultiLineString($matches[2]),
                self::LINEAR_RING => $this->parseLinearRing($matches[2]),
                self::POLYGON => $this->parsePolygon($matches[2]),
                self::MULTI_POLYGON => $this->parseMultiPolygon($matches[2]),
                self::GEOMETRY_COLLECTION => $this->parseGeometryCollection($matches[2]),
            };
        } catch (Exception $e) {
            throw new InvalidWktException($text, $e);
        }
    }

    private function parsePoint(string $text): PointInterface
    {
        $parsed_text = preg_split('/\s+/', trim($text));

        return new Point(
            new Coordinate((float) $parsed_text[0]),
            new Coordinate((float) $parsed_text[1]),
        );
    }

    private function parseMultiPoint(string $str): MultiPointInterface
    {
        $str = trim($str);

        if (empty($str)) {
            return new MultiPoint();
        }

        return new MultiPoint(...$this->parseLineString($str)->getPoints());
    }

    private function parseLineString(string $text): LineStringInterface
    {
        $points = [];

        foreach (preg_split('/,/', trim($text)) as $point_text) {
            $points[] = $this->parsePoint($point_text);
        }

        return new LineString(...$points);
    }

    private function parseMultiLineString(string $text): MultiLineStringInterface
    {
        return new MultiLineString(...$this->parseCollection($text, self::LINE_STRING));
    }

    private function parseLinearRing(string $text): LinearRingInterface
    {
        return new LinearRing(...$this->parseLineString($text)->getPoints());
    }

    private function parsePolygon(string $text): PolygonInterface
    {
        $boundaries = $this->parseCollection($text, self::LINEAR_RING);
        $exterior_boundary = array_pop($boundaries);

        return new Polygon($exterior_boundary, ...$boundaries);
    }

    private function parseMultiPolygon($str): MultiPolygonInterface
    {
        return new MultiPolygon(...$this->parseCollection($str, "Polygon"));
    }

    private function parseGeometryCollection(string $text): GeometryCollectionInterface
    {
        $components = [];
        foreach (preg_split('/,\s*(?=[A-Za-z])/', trim($text)) as $parse_geometry_text) {
            $components[] = $this->parse($parse_geometry_text);
        }
        return new GeometryCollection(...$components);
    }

    private function parseCollection(
        string $text,
        string $child_constructor
    ): array
    {
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
