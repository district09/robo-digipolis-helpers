<?php

namespace DigipolisGent\Robo\Helpers\Traits;

use Roave\BetterReflection\Reflection\ReflectionClass;

trait TraitDependencyCheckerTrait
{
    protected function checkTraitDependencies()
    {
        $traitNames = $this->getAllTraits(get_class($this));
        foreach ($traitNames as $traitName) {
            $trait = ReflectionClass::createFromName($traitName);
            $dependencyMethod = 'get' . $trait->getShortName() . 'Dependencies';
            if (!$trait->hasMethod($dependencyMethod)) {
                continue;
            }
            if ($missing = array_diff($this->{$dependencyMethod}(), $traitNames)) {
                throw new \Exception(get_class($this) . ' uses trait ' . $trait->getName() . ', but is missing some of its dependend traits: ' . implode(', ', $missing));
            }
        }
    }

    protected function getAllTraits($className)
    {
        $reflection = ReflectionClass::createFromName($className);
        $traitNames = $reflection->getTraitNames();
        $traitUsers = array_merge($traitNames, $reflection->getParentClassNames());
        foreach ($traitUsers as $traitUser) {
            $traitNames = array_merge($traitNames, $this->getAllTraits($traitUser));
        }
        return $traitNames;
    }
}
