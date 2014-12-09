<?php
namespace Clown\Traits\Relationship;

trait Operation
{
    public function destroy()
    {
        foreach ($this->_getMetadata() as $metadata) {
            if (in_array($metadata['dependent'], array('update', 'delete'))) {
                $attributes = array($metadata['foreignKey'] => null);
                if ($metadata['dependent'] === 'delete') {
                    $attributes[$metadata['class']::$disabled] = true;
                }
                $metadata['class']::updateAll(
                    $attributes,
                    $this->_getConditions($metadata)
                );
            } else {
                $metadata['class']::destroyAll($this->_getConditions($metadata));
            }
        }

        $this->_resetRecordIterator();
    }
}