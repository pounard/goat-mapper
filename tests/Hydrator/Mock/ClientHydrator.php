<?php

namespace Goat\Mapper\Tests\Hydrator\Mock;

class ClientHydrator implements \Zend\Hydrator\HydratorInterface
{
    private $hydrateCallbacks = array(), $extractCallbacks = array();
    function __construct()
    {
        $this->hydrateCallbacks[] = \Closure::bind(static function ($object, $values) {
            if (isset($values['id']) || $object->id !== null && \array_key_exists('id', $values)) {
                $object->id = $values['id'];
            }
            if (isset($values['firstname']) || $object->firstname !== null && \array_key_exists('firstname', $values)) {
                $object->firstname = $values['firstname'];
            }
            if (isset($values['lastname']) || $object->lastname !== null && \array_key_exists('lastname', $values)) {
                $object->lastname = $values['lastname'];
            }
            $object->addresses = $values['addresses'] ?? null;
            if (isset($values['advisorId']) || $object->advisorId !== null && \array_key_exists('advisorId', $values)) {
                $object->advisorId = $values['advisorId'];
            }
            $object->personalAdvisor = $values['personalAdvisor'] ?? null;
        }, null, 'Goat\\Mapper\\Tests\\Mock\\Client');
        $this->extractCallbacks[] = \Closure::bind(static function ($object, &$values) {
            $values['id'] = $object->id;
            $values['firstname'] = $object->firstname;
            $values['lastname'] = $object->lastname;
            $values['addresses'] = $object->addresses;
            $values['advisorId'] = $object->advisorId;
            $values['personalAdvisor'] = $object->personalAdvisor;
        }, null, 'Goat\\Mapper\\Tests\\Mock\\Client');
    }
    function hydrate(array $data, $object)
    {
        $this->hydrateCallbacks[0]->__invoke($object, $data);
        return $object;
    }
    function extract($object)
    {
        $ret = array();
        $this->extractCallbacks[0]->__invoke($object, $ret);
        return $ret;
    }
}