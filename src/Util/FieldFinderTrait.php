<?php

namespace Bdf\Form\Util;

use Bdf\Form\ElementInterface;

/**
 * Helper trait for adding field finding methods
 * Must be used on a ChildAggregateInterface implementation, like a subclass of CustomForm
 *
 * Usage:
 * <code>
 * class CredentialsForm extends CustomForm
 * {
 *     use FieldFinderTrait;
 *
 *     protected function configure(FormBuilderInterface $builder): void
 *     {
 *         $builder->string('username')->required();
 *         $builder->string('password')->required()->length(['min' => 6]);
 *         $builder->string('confirm')->depends('password')->satisfy(function ($value) {
 *             if ($value !== $this->findFieldValue('password')) {
 *                 return 'Invalid password confirmation';
 *             }
 *         });
 *     }
 * }
 * </code>
 */
trait FieldFinderTrait
{
    /**
     * Find a child field by a path
     *
     * Usage:
     * <code>
     * $this->findField('/foo/bar'); // Find field bar, under foo element, starting from the root
     * $this->findField('foo'); // Find field foo of the current form
     * $this->findField('../foo'); // Find field foo of the parent form (i.e. the current form is embedded)
     * </code>
     *
     * @param string $path The field path
     *
     * @return ElementInterface|null The element, or null if not found
     *
     * @see FieldPath::parse() For the path syntax
     */
    public function findField(string $path): ?ElementInterface
    {
        return $this->fieldPath($path)->resolve($this);
    }

    /**
     * Get a child field value by a path
     *
     * Usage:
     * <code>
     * $this->findField('/foo/bar'); // Get value of field bar, under foo element, starting from the root
     * $this->findField('foo'); // Get value of field foo on the current form
     * $this->findField('../foo'); // Get value of field foo on the parent form (i.e. the current form is embedded)
     * </code>
     *
     * @param string $path The field path
     *
     * @return mixed The field value, or null if the field is not found
     *
     * @see FieldPath::parse() For the path syntax
     */
    public function findFieldValue(string $path)
    {
        return $this->fieldPath($path)->value($this);
    }

    /**
     * Parse the field path
     *
     * @param string $path
     *
     * @return FieldPath
     */
    private function fieldPath(string $path): FieldPath
    {
        if ($path[0] !== '.' && $path[0] !== '/') {
            $path = './'.$path;
        }

        return FieldPath::parse($path);
    }
}
