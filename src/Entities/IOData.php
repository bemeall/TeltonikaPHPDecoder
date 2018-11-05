<?php
/**
 * Created by PhpStorm.
 * User: Alvaro
 * Date: 12/07/2018
 * Time: 19:49
 */

namespace lbarrous\TeltonikaDecoder\Entities;


class IOData implements \JsonSerializable
{
    private $eventID;
    private $elementCount;
    private $ID;
    private $value;

    /**
     * IOData constructor.
     * @param $eventID
     * @param $elementCount
     * @param $ID
     * @param $value
     */
    public function __construct($eventID, $elementCount, $ID, $value)
    {
        $this->eventID = $eventID;
        $this->elementCount = $elementCount;
        $this->ID = $ID;
        $this->value = $value;
    }

    /**
     * @return mixed
     */
    public function getEventID()
    {
        return $this->eventID;
    }

    /**
     * @param mixed $eventID
     */
    public function setEventID($eventID)
    {
        $this->eventID = $eventID;
    }

    /**
     * @return mixed
     */
    public function getElementCount()
    {
        return $this->elementCount;
    }

    /**
     * @param mixed $elementCount
     */
    public function setElementCount($elementCount)
    {
        $this->elementCount = $elementCount;
    }

    /**
     * @return mixed
     */
    public function getID()
    {
        return $this->ID;
    }

    /**
     * @param mixed $ID
     */
    public function setID($ID)
    {
        $this->ID = $ID;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return
            [
                'eventID'   => $this->getEventID(),
                'elementCount'   => $this->getElementCount(),
                'ID'   => $this->getID(),
                'value'   => $this->getValue(),
            ];
    }
}