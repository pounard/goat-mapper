<?php

namespace Goat\Mapper\Tests\Hydrator\Mock;

class AddressHydrator implements \Zend\Hydrator\HydratorInterface
{
    private $hydrateCallbacks = array(), $extractCallbacks = array();
    function __construct()
    {
        $this->hydrateCallbacks[] = \Closure::bind(static function ($object, $values) {
            if (isset($values['id']) || $object->id !== null && \array_key_exists('id', $values)) {
                $object->id = $values['id'];
            }
            if (isset($values['type']) || $object->type !== null && \array_key_exists('type', $values)) {
                $object->type = $values['type'];
            }
            if (isset($values['clientId']) || $object->clientId !== null && \array_key_exists('clientId', $values)) {
                $object->clientId = $values['clientId'];
            }
            if (isset($values['client']) || $object->client !== null && \array_key_exists('client', $values)) {
                $object->client = $values['client'];
            }
            if (isset($values['line1']) || $object->line1 !== null && \array_key_exists('line1', $values)) {
                $object->line1 = $values['line1'];
            }
            if (isset($values['line2']) || $object->line2 !== null && \array_key_exists('line2', $values)) {
                $object->line2 = $values['line2'];
            }
            if (isset($values['locality']) || $object->locality !== null && \array_key_exists('locality', $values)) {
                $object->locality = $values['locality'];
            }
            if (isset($values['zipCode']) || $object->zipCode !== null && \array_key_exists('zipCode', $values)) {
                $object->zipCode = $values['zipCode'];
            }
            if (isset($values['countryCode']) || $object->countryCode !== null && \array_key_exists('countryCode', $values)) {
                $object->countryCode = $values['countryCode'];
            }
            $object->country = $values['country'] ?? null;
        }, null, 'Goat\\Mapper\\Tests\\Mock\\Address');
        $this->extractCallbacks[] = \Closure::bind(static function ($object, &$values) {
            $values['id'] = $object->id;
            $values['type'] = $object->type;
            $values['clientId'] = $object->clientId;
            $values['client'] = $object->client;
            $values['line1'] = $object->line1;
            $values['line2'] = $object->line2;
            $values['locality'] = $object->locality;
            $values['zipCode'] = $object->zipCode;
            $values['countryCode'] = $object->countryCode;
            $values['country'] = $object->country;
        }, null, 'Goat\\Mapper\\Tests\\Mock\\Address');
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