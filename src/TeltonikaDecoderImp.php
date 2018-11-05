<?php
/**
 * Created by PhpStorm.
 * User: Alvaro
 * Date: 12/07/2018
 * Time: 19:47
 */

namespace lbarrous\TeltonikaDecoder;

use lbarrous\TeltonikaDecoder\Entities\AVLData;
use lbarrous\TeltonikaDecoder\Entities\GPSData;
use lbarrous\TeltonikaDecoder\Entities\IOData;
use lbarrous\TeltonikaDecoder\Entities\ImeiNumber;

class TeltonikaDecoderImp implements TeltonikaDecoder
{

    const HEX_DATA_LENGHT = 100;
    const HEX_DATA_HEADER = 20;

    const CODEC8 = 8;
    const CODEC7 = 7;
    const CODEC16 = 16;

    const TIMESTAMP_HEX_LENGTH = 16;
    const PRIORITY_HEX_LENGTH = 2;
    const LONGITUDE_HEX_LENGTH = 8;
    const LATITUDE_HEX_LENGTH = 8;
    const ALTITUDE_HEX_LENGTH = 4;
    const ANGLE_HEX_LENGTH = 4;
    const SATELLITES_HEX_LENGTH = 2;
    const SPEED_HEX_LENGTH = 4;

    const EVENTID_HEX_LENGTH = 2;
    const ELEMENTCOUNT_HEX_LENGTH = 2;
    const ID_HEX_LENGTH = 2;
    const VALUE_HEX_LENGTH = 2;

    const ELEMENT_COUNT_1B_HEX_LENGTH = 2;
    const ELEMENT_COUNT_2B_HEX_LENGTH = 2;
    const ELEMENT_COUNT_4B_HEX_LENGTH = 2;
    const ELEMENT_COUNT_8B_HEX_LENGTH = 2;

    const VALUE_1B_HEX_LENGTH = 2;
    const VALUE_2B_HEX_LENGTH = 4;
    const VALUE_4B_HEX_LENGTH = 8;
    const VALUE_8B_HEX_LENGTH = 16;

    private $imei;
    private $dataFromDevice;
    private $AVLData;
    private $pointer;

    /**
     * TeltonikaDecoderImp constructor.
     * @param $dataFromDevice
     */
    public function __construct(string $dataFromDevice, $imei)
    {
        $this->dataFromDevice = $dataFromDevice;
        $this->imei = $imei;
        $this->AVLData = array();
        $this->pointer = self::HEX_DATA_HEADER;
    }


    public function getNumberOfElements(): int
    {
        $dataCountHex = substr($this->dataFromDevice,18,2);
        $dataCountDecimal = hexdec($dataCountHex);

        return $dataCountDecimal;
    }

    public function getCodecType(): int
    {
        $codecTypeHex = substr($this->dataFromDevice,16,2);
        $codecTypeDecimal = hexdec($codecTypeHex);

        return $codecTypeDecimal;
    }

    public function decodeAVLArrayData(string $hexDataOfElement) :AVLData
    {
        $codecType = $this->getCodecType();

        if($codecType == self::CODEC8) {
            return $this->codec8Decode($hexDataOfElement);
        }

    }

    public function getArrayOfAllData(): array
    {
        $AVLArray = array();

        $hexDataWithoutCRC = substr($this->dataFromDevice, 0, -8);
        $hexAVLDataArray = substr($hexDataWithoutCRC, self::HEX_DATA_HEADER);

        $dataCount = $this->getNumberOfElements();

        $startPosition = $this->pointer;

        for($i=0; $i<$dataCount; $i++) {
            $hexDataOfElement = substr($hexDataWithoutCRC,$startPosition,strlen($hexDataWithoutCRC));

            //Decode and add to array of elements
            $AVLArray[] = $this->decodeAVLArrayData($hexDataOfElement);

            $startPosition += $this->pointer;
        }

        return $AVLArray;
    }

    private function codec8Decode(string $hexDataOfElement) :AVLData {

        $arrayElement = array();
        $IOElements = [];

        $AVLElement = new AVLData();

        $AVLElement->setImei($this->imei);

        //We only get first 10 characters to get timestamp up to seconds.
        $timestamp = substr(hexdec(substr($hexDataOfElement, 0, self::TIMESTAMP_HEX_LENGTH)), 0, 10);
        $dateTimeWithoutFormat = new \DateTime();
        $dateTimeWithoutFormat->setTimestamp(intval($timestamp));
        $dateTimeWithFormat =  $dateTimeWithoutFormat->format('Y-m-d H:i:s') . "\n";

        $AVLElement->setTimestamp($timestamp);
        $AVLElement->setDateTime($dateTimeWithFormat);

        $stringSplitter = self::TIMESTAMP_HEX_LENGTH;
        $priority = hexdec(substr($hexDataOfElement, $stringSplitter, self::PRIORITY_HEX_LENGTH));
        $AVLElement->setPriority($priority);
        $stringSplitter+= self::PRIORITY_HEX_LENGTH;
        $longitudeValueOnArrayTwoComplement = unpack("l", pack("l", hexdec(substr($hexDataOfElement, $stringSplitter, self::LONGITUDE_HEX_LENGTH))));
        $longitude = (float) (reset($longitudeValueOnArrayTwoComplement) / 10000000);
        $stringSplitter+= self::LONGITUDE_HEX_LENGTH;
        $latitudeValueOnArrayTwoComplement = unpack("l", pack("l", hexdec(substr($hexDataOfElement, $stringSplitter, self::LATITUDE_HEX_LENGTH))));
        $latitude = (float) (reset($latitudeValueOnArrayTwoComplement) / 10000000);
        $stringSplitter+= self::LATITUDE_HEX_LENGTH;
        $altitude = hexdec(substr($hexDataOfElement, $stringSplitter, self::ALTITUDE_HEX_LENGTH));
        $stringSplitter+= self::ALTITUDE_HEX_LENGTH;
        $angle = hexdec(substr($hexDataOfElement, $stringSplitter, self::ANGLE_HEX_LENGTH));
        $stringSplitter+= self::ANGLE_HEX_LENGTH;
        $satellites = hexdec(substr($hexDataOfElement, $stringSplitter, self::SATELLITES_HEX_LENGTH));
        $stringSplitter+= self::SATELLITES_HEX_LENGTH;
        $speed = hexdec(substr($hexDataOfElement, $stringSplitter, self::SPEED_HEX_LENGTH));
        $stringSplitter+= self::SPEED_HEX_LENGTH;

        $GPSData = new GPSData($longitude, $latitude, $altitude, $angle, $satellites, $speed);

        $AVLElement->setGpsData($GPSData);

        //io
        $eventID = hexdec(substr($hexDataOfElement, $stringSplitter, self::EVENTID_HEX_LENGTH));
        $stringSplitter+= self::EVENTID_HEX_LENGTH;

        $elementCount = hexdec(substr($hexDataOfElement, $stringSplitter, self::ELEMENTCOUNT_HEX_LENGTH));
        $stringSplitter+= self::ELEMENTCOUNT_HEX_LENGTH;

        // 1byte I/o
        $elementCount1B = hexdec(substr($hexDataOfElement, $stringSplitter, self::ELEMENT_COUNT_1B_HEX_LENGTH));
        $stringSplitter+= self::ELEMENT_COUNT_1B_HEX_LENGTH;

        for ($i = 0; $i < $elementCount1B; $i++) {
            $ID = hexdec(substr($hexDataOfElement, $stringSplitter, self::ID_HEX_LENGTH));
            $stringSplitter+= self::ID_HEX_LENGTH;

            $value = hexdec(substr($hexDataOfElement, $stringSplitter, self::VALUE_1B_HEX_LENGTH));
            $stringSplitter+= self::VALUE_1B_HEX_LENGTH;

            $IOElements[] = new Entities\IOData($eventID, $elementCount, $ID, $value);
        }

        // 2byte I/o
        $elementCount2B = hexdec(substr($hexDataOfElement, $stringSplitter, self::ELEMENT_COUNT_2B_HEX_LENGTH));
        $stringSplitter+= self::ELEMENT_COUNT_2B_HEX_LENGTH;

        for ($i = 0; $i < $elementCount2B; $i++) {
            $ID = hexdec(substr($hexDataOfElement, $stringSplitter, self::ID_HEX_LENGTH));
            $stringSplitter+= self::ID_HEX_LENGTH;

            $value = hexdec(substr($hexDataOfElement, $stringSplitter, self::VALUE_2B_HEX_LENGTH));
            $stringSplitter+= self::VALUE_2B_HEX_LENGTH;

            $IOElements[] = new Entities\IOData($eventID, $elementCount, $ID, $value);
        }

        // 4byte I/o
        $elementCount4B = hexdec(substr($hexDataOfElement, $stringSplitter, self::ELEMENT_COUNT_4B_HEX_LENGTH));
        $stringSplitter+= self::ELEMENT_COUNT_4B_HEX_LENGTH;

        for ($i = 0; $i < $elementCount4B; $i++) {
            $ID = hexdec(substr($hexDataOfElement, $stringSplitter, self::ID_HEX_LENGTH));
            $stringSplitter+= self::ID_HEX_LENGTH;

            $value = hexdec(substr($hexDataOfElement, $stringSplitter, self::VALUE_4B_HEX_LENGTH));
            $stringSplitter+= self::VALUE_4B_HEX_LENGTH;

            $IOElements[] = new Entities\IOData($eventID, $elementCount, $ID, $value);
        }

        // 8byte I/o
        $elementCount8B = hexdec(substr($hexDataOfElement, $stringSplitter, self::ELEMENT_COUNT_8B_HEX_LENGTH));
        $stringSplitter+= self::ELEMENT_COUNT_8B_HEX_LENGTH;

        for ($i = 0; $i < $elementCount8B; $i++) {
            $ID = hexdec(substr($hexDataOfElement, $stringSplitter, self::ID_HEX_LENGTH));
            $stringSplitter+= self::ID_HEX_LENGTH;

            $value = hexdec(substr($hexDataOfElement, $stringSplitter, self::VALUE_8B_HEX_LENGTH));
            $stringSplitter+= self::VALUE_8B_HEX_LENGTH;

            $IOElements[] = new Entities\IOData($eventID, $elementCount, $ID, $value);
        }

        $this->pointer = $stringSplitter;

        $AVLElement->setIOData($IOElements);
        return $AVLElement;

    }

    private function convert($hex)
    {
        $dec = hexdec($hex);
        return ($dec < 0x7fffffff) ? $dec
            : 0 - (0xffffffff - $dec);
    }
}