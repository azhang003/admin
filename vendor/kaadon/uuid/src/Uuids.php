<?php

namespace Kaadon\Uuid;

use Ramsey\Uuid\Provider\Node\StaticNodeProvider;
use Ramsey\Uuid\Type\Hexadecimal;
use Ramsey\Uuid\Type\Integer;
use Ramsey\Uuid\Uuid;

class Uuids
{
    public static function getUuid1(int $hexadecimalNumber = null){

        if (is_null($hexadecimalNumber)){

            $hexadecimalNumber = random_int('1111111111','9999999999');

        }
        $nodeProvider = new StaticNodeProvider(new Hexadecimal($hexadecimalNumber));

        $clockSequence = time();

        $uuid = Uuid::uuid1($nodeProvider->getNode(), $clockSequence);

        return $uuid->toString();
    }

    public static function getUuid2(int $hexadecimalNumber = null){

        $localId = time();

        if (is_null($hexadecimalNumber)){

            $hexadecimalNumber = random_int('1111111111','9999999999');

        }
        $localId = new Integer($localId);

        $nodeProvider = new StaticNodeProvider(new Hexadecimal($hexadecimalNumber));

        $clockSequence = random_int(1,63);

        $uuid = Uuid::uuid2(
            Uuid::DCE_DOMAIN_ORG,
            $localId,
            $nodeProvider->getNode(),
            $clockSequence
        );
        return $uuid;
    }

    public static function getUuid3(string $Identification = null){

        if (is_null($Identification)){

            $Identification = md5(msectime());

        }

        $uuid = Uuid::uuid3(self::getUuid1()->toString(), $Identification);

        return $uuid;
    }

    public static function getUuid4(){

        $uuid = Uuid::uuid4();

        return $uuid;
    }

    public static function getUuid5(string $Identification = null){

        if (is_null($Identification)){

            $Identification = md5(msectime());

        }

        $uuid = Uuid::uuid5(self::getUuid1()->toString(), $Identification);

        return $uuid;
    }

    public static function getUuid6(int $hexadecimalNumber = null){

        if (is_null($hexadecimalNumber)){

            $hexadecimalNumber = random_int('1111111111','9999999999');

        }
        $nodeProvider = new StaticNodeProvider(new Hexadecimal($hexadecimalNumber));

        $clockSequence = time();

        $uuid = Uuid::uuid6($nodeProvider->getNode(), $clockSequence);

        return $uuid;
    }
}