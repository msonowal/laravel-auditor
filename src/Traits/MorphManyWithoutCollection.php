<?php

namespace Msonowal\Audit\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;

trait MorphManyWithoutCollection
{
    /**
     * Define a polymorphic one-to-many relationship.
     *
     * @param string $related
     * @param string $name
     * @param string $type
     * @param string $id
     * @param string $localKey
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function morphManyWithoutCollection($related, $name, $type = null, $id = null, $localKey = null): MorphMany
    {
        $instance = $this->newRelatedInstance($related);

        // Here we will gather up the morph type and ID for the relationship so that we
        // can properly query the intermediate table of a relation.
        list($type, $id) = $this->getMorphs($name, null, null);

        $localKey = $this->getKeyName();

        return $this->newMorphMany($instance->newQuery(), $this, $type, $id, $localKey);
    }
}
