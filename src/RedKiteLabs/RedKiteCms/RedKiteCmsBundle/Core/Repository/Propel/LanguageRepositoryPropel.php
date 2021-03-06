<?php
/**
 * This file is part of the RedKite CMS Application and it is distributed
 * under the MIT License. To use this application you must leave
 * intact this copyright notice.
 *
 * Copyright (c) RedKite Labs <webmaster@redkite-labs.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * For extra documentation and help please visit http://www.redkite-labs.com
 *
 * @license    MIT License
 *
 */

namespace RedKiteLabs\RedKiteCms\RedKiteCmsBundle\Core\Repository\Propel;

use RedKiteLabs\RedKiteCms\RedKiteCmsBundle\Model\Language;
use RedKiteLabs\RedKiteCms\RedKiteCmsBundle\Model\LanguageQuery;
use RedKiteLabs\RedKiteCms\RedKiteCmsBundle\Core\Repository\Repository\LanguageRepositoryInterface;
use RedKiteLabs\RedKiteCms\RedKiteCmsBundle\Core\Exception\Content\General\InvalidArgumentTypeException;

/**
 *  Implements the LanguageRepositoryInterface to work with Propel
 *
 *  @author RedKite Labs <webmaster@redkite-labs.com>
 */
class LanguageRepositoryPropel extends Base\PropelRepository implements LanguageRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function getRepositoryObjectClassName()
    {
        return '\RedKiteLabs\RedKiteCms\RedKiteCmsBundle\Model\Language';
    }

    /**
     * {@inheritdoc}
     */
    public function setRepositoryObject($object = null)
    {
        if (null !== $object && !$object instanceof Language) {
            throw new InvalidArgumentTypeException('exception_only_propel_language_objects_are_accepted');
        }

        return parent::setRepositoryObject($object);
    }

    /**
     * {@inheritdoc}
     */
    public function fromPK($id)
    {
        return LanguageQuery::create()->findPk($id);
    }

    /**
     * {@inheritdoc}
     */
    public function mainLanguage()
    {
        return LanguageQuery::create()
                    ->filterByMainLanguage(1)
                    ->filterByToDelete(0)
                    ->findOne();
    }

    /**
     * {@inheritdoc}
     */
    public function fromLanguageName($languageName)
    {
        if (null === $languageName) {
            return null;
        }

        if (!is_string($languageName)) {
            throw new InvalidArgumentTypeException('exception_invalid_value_for_fromLanguageName_method');
        }

        return LanguageQuery::create()
                    ->filterByToDelete(0)
                    ->filterByLanguageName($languageName)
                    ->findOne();
    }

    /**
     * {@inheritdoc}
     */
    public function activeLanguages()
    {
        return LanguageQuery::create()
                ->filterByToDelete(0)
                ->where('id > 1')
                ->orderBy( 'languageName' )
                ->find();
    }

    /**
     * {@inheritdoc}
     */
    public function firstOne()
    {
        return LanguageQuery::create()
                    ->filterByToDelete(0)
                    ->where('id > 1')
                    ->findOne();
    }
}
