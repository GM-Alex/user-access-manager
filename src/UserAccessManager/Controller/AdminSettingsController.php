<?php
/**
 * AdminSettingsController.php
 *
 * The AdminSettingsController class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\Controller;

use UserAccessManager\Config\Config;
use UserAccessManager\FileHandler\FileHandler;
use UserAccessManager\Form\FormFactory;
use UserAccessManager\Form\FormHelper;
use UserAccessManager\ObjectHandler\ObjectHandler;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;

class AdminSettingsController extends Controller
{
    const SETTING_GROUP_PARAMETER = 'settings_group';
    const SETTING_SECTION_PARAMETER = 'settings_section';
    const GROUP_POST_TYPES = 'post_types';
    const GROUP_TAXONOMIES = 'taxonomies';
    const GROUP_FILES = 'file';
    const SECTION_FILES = 'file';
    const GROUP_AUTHOR = 'author';
    const SECTION_AUTHOR = 'author';
    const GROUP_OTHER = 'other';
    const SECTION_OTHER = 'other';

    /**
     * @var string
     */
    protected $template = 'AdminSettings.php';

    /**
     * @var ObjectHandler
     */
    private $objectHandler;

    /**
     * @var FileHandler
     */
    private $fileHandler;

    /**
     * @var FormFactory
     */
    private $formFactory;

    /**
     * @var FormHelper
     */
    private $formHelper;

    /**
     * AdminSettingsController constructor.
     *
     * @param Php           $php
     * @param Wordpress     $wordpress
     * @param Config        $config
     * @param ObjectHandler $objectHandler
     * @param FileHandler   $fileHandler
     * @param FormFactory   $formFactory
     * @param FormHelper   $formHelper
     */
    public function __construct(
        Php $php,
        Wordpress $wordpress,
        Config $config,
        ObjectHandler $objectHandler,
        FileHandler $fileHandler,
        FormFactory $formFactory,
        FormHelper $formHelper
    ) {
        parent::__construct($php, $wordpress, $config);
        $this->objectHandler = $objectHandler;
        $this->fileHandler = $fileHandler;
        $this->formFactory = $formFactory;
        $this->formHelper = $formHelper;
    }

    /**
     * Returns true if the server is a nginx server.
     *
     * @return bool
     */
    public function isNginx()
    {
        return $this->wordpress->isNginx();
    }

    /**
     * Returns the pages.
     *
     * @return array
     */
    private function getPages()
    {
        $pages = $this->wordpress->getPages('sort_column=menu_order');
        return is_array($pages) !== false ? $pages : [];
    }

    /**
     * Returns the post types as object.
     *
     * @return \WP_Post_Type[]
     */
    private function getPostTypes()
    {
        return $this->wordpress->getPostTypes(['public' => true], 'objects');
    }

    /**
     * Returns the taxonomies as objects.
     *
     * @return \WP_Taxonomy[]
     */
    private function getTaxonomies()
    {
        return $this->wordpress->getTaxonomies(['public' => true], 'objects');
    }

    /**
     * @param string $key
     * @param bool   $description
     *
     * @return string
     */
    public function getText($key, $description = false)
    {
        return $this->formHelper->getText($key, $description);
    }

    /**
     * Returns the object name.
     *
     * @param string $objectKey
     *
     * @return string
     */
    public function getObjectName($objectKey)
    {
        $objects = $this->wordpress->getPostTypes(['public' => true], 'objects')
            + $this->wordpress->getTaxonomies(['public' => true], 'objects');

        return (isset($objects[$objectKey]) === true) ? $objects[$objectKey]->labels->name : $objectKey;
    }

    /**
     * Returns the current settings group.
     *
     * @return string
     */
    public function getCurrentSettingsGroup()
    {
        return (string)$this->getRequestParameter(self::SETTING_GROUP_PARAMETER, self::GROUP_POST_TYPES);
    }

    /**
     * Returns the current settings group.
     *
     * @return string
     */
    public function getCurrentSettingsSection()
    {
        $default = null;
        $group = $this->getCurrentSettingsGroup();

        if ($group === self::GROUP_POST_TYPES || $group === self::GROUP_TAXONOMIES) {
            $default = Config::DEFAULT_TYPE;
        } elseif ($group === self::GROUP_FILES) {
            $default = self::SECTION_FILES;
        } elseif ($group === self::GROUP_AUTHOR) {
            $default = self::SECTION_AUTHOR;
        } elseif ($group === self::GROUP_OTHER) {
            $default = self::SECTION_OTHER;
        }

        return (string)$this->getRequestParameter(self::SETTING_SECTION_PARAMETER, $default);
    }

    /**
     * Returns the settings group link by the given group key.
     *
     * @param string $groupKey
     *
     * @return string
     */
    public function getSettingsGroupLink($groupKey)
    {
        $rawUrl = $this->getRequestUrl();
        $url = preg_replace('/&amp;'.self::SETTING_GROUP_PARAMETER.'[^&]*/i', '', $rawUrl);
        return $url.'&'.self::SETTING_GROUP_PARAMETER.'='.$groupKey;
    }

    /**
     * Returns the settings section link by the given group and section key.
     *
     * @param string $groupKey
     * @param string $sectionKey
     *
     * @return string
     */
    public function getSectionGroupLink($groupKey, $sectionKey)
    {
        $rawUrl = $this->getSettingsGroupLink($groupKey);
        $url = preg_replace('/&amp;'.self::SETTING_SECTION_PARAMETER.'[^&]*/i', '', $rawUrl);
        return $url.'&'.self::SETTING_SECTION_PARAMETER.'='.$sectionKey;
    }

    /**
     * Returns the post settings form.
     *
     * @param string $postType
     *
     * @return \UserAccessManager\Form\Form
     */
    private function getPostSettingsForm($postType = Config::DEFAULT_TYPE)
    {
        $textarea = null;
        $configParameters = $this->config->getConfigParameters();

        if (isset($configParameters["{$postType}_content"]) === true) {
            $configParameter = $configParameters["{$postType}_content"];
            $textarea = $this->formFactory->createTextarea(
                $configParameter->getId(),
                $configParameter->getValue(),
                $this->formHelper->getParameterText($configParameter, false, $postType),
                $this->formHelper->getParameterText($configParameter, true, $postType)
            );
        }

        $parameters = ($postType !== Config::DEFAULT_TYPE) ? ["{$postType}_use_default"] : [];
        $parameters = array_merge($parameters, [
            "hide_{$postType}",
            "hide_{$postType}_title",
            "{$postType}_title",
            $textarea,
            "hide_{$postType}_comment",
            "{$postType}_comment_content",
            "{$postType}_comments_locked"
        ]);

        if ($postType === 'post') {
            $parameters[] = "show_{$postType}_content_before_more";
        }

        return $this->formHelper->getSettingsForm($parameters, $postType);
    }

    /**
     * Returns the taxonomy settings form.
     *
     * @param string $taxonomy
     *
     * @return \UserAccessManager\Form\Form
     */
    private function getTaxonomySettingsForm($taxonomy = Config::DEFAULT_TYPE)
    {
        $parameters = ($taxonomy !== Config::DEFAULT_TYPE) ? ["{$taxonomy}_use_default"] : [];
        $parameters = array_merge($parameters, [
            "hide_empty_{$taxonomy}"
        ]);

        return $this->formHelper->getSettingsForm($parameters, $taxonomy);
    }

    /**
     * Returns the files settings form.
     *
     * @return \UserAccessManager\Form\Form
     */
    private function getFilesSettingsForm()
    {
        $parameters = [
            'lock_file',
            'download_type'
        ];

        $configParameters = $this->config->getConfigParameters();

        if (isset($configParameters['lock_file_types']) === true) {
            $values = [
                $this->formFactory->createMultipleFormElementValue('all', TXT_UAM_ALL)
            ];

            if (isset($configParameters['locked_file_types']) === true) {
                $lockFileTypes = $configParameters['locked_file_types'];
                $selectedValue = $this->formFactory->createMultipleFormElementValue(
                    'selected',
                    TXT_UAM_LOCKED_FILE_TYPES
                );
                $selectedValue->setSubElement($this->formHelper->convertConfigParameter($lockFileTypes));
                $values[] = $selectedValue;
            }

            if ($this->wordpress->isNginx() === false
                && isset($configParameters['not_locked_file_types']) === true
            ) {
                $notLockFileTypes = $configParameters['not_locked_file_types'];
                $notSelectedValue = $this->formFactory->createMultipleFormElementValue(
                    'not_selected',
                    TXT_UAM_NOT_LOCKED_FILE_TYPES
                );
                $notSelectedValue->setSubElement($this->formHelper->convertConfigParameter($notLockFileTypes));
                $values[] = $notSelectedValue;
            }

            $configParameter = $configParameters['lock_file_types'];

            $lockFileTypes = $this->formFactory->createRadio(
                $configParameter->getId(),
                $values,
                $configParameter->getValue(),
                TXT_UAM_LOCK_FILE_TYPES,
                TXT_UAM_LOCK_FILE_TYPES_DESC
            );
            $parameters[] = $lockFileTypes;
        }

        $parameters[] = 'file_pass_type';

        return $this->formHelper->getSettingsForm($parameters);
    }

    /**
     * Returns the author settings form.
     *
     * @return \UserAccessManager\Form\Form
     */
    private function getAuthorSettingsForm()
    {
        $parameters = [
            'authors_has_access_to_own',
            'authors_can_add_posts_to_groups',
            'full_access_role'
        ];

        return $this->formHelper->getSettingsForm($parameters);
    }

    /**
     * Returns the author settings form.
     *
     * @return \UserAccessManager\Form\Form
     */
    private function getOtherSettingsForm()
    {
        $redirect = null;
        $configParameters = $this->config->getConfigParameters();

        if (isset($configParameters['redirect'])) {
            $values = [
                $this->formFactory->createMultipleFormElementValue('false', TXT_UAM_NO),
                $this->formFactory->createMultipleFormElementValue('blog', TXT_UAM_REDIRECT_TO_BLOG),
            ];

            if (isset($configParameters['redirect_custom_page']) === true) {
                $redirectCustomPage = $configParameters['redirect_custom_page'];
                $redirectCustomPageValue = $this->formFactory->createMultipleFormElementValue(
                    'selected',
                    TXT_UAM_REDIRECT_TO_PAGE
                );

                $possibleValues = [];
                $pages = $this->getPages();

                foreach ($pages as $page) {
                    $possibleValues[] = $this->formFactory->createValueSetFromElementValue(
                        (int)$page->ID,
                        $page->post_title
                    );
                }

                $formElement = $this->formFactory->createSelect(
                    $redirectCustomPage->getId(),
                    $possibleValues,
                    (int)$redirectCustomPage->getValue()
                );

                $redirectCustomPageValue->setSubElement($formElement);
                $values[] = $redirectCustomPageValue;
            }

            if (isset($configParameters['redirect_custom_url']) === true) {
                $redirectCustomUrl = $configParameters['redirect_custom_url'];
                $redirectCustomUrlValue = $this->formFactory->createMultipleFormElementValue(
                    'custom_url',
                    TXT_UAM_REDIRECT_TO_URL
                );
                $redirectCustomUrlValue->setSubElement($this->formHelper->convertConfigParameter($redirectCustomUrl));
                $values[] = $redirectCustomUrlValue;
            }

            $configParameter = $configParameters['redirect'];

            $redirect = $this->formFactory->createRadio(
                $configParameter->getId(),
                $values,
                $configParameter->getValue(),
                TXT_UAM_REDIRECT,
                TXT_UAM_REDIRECT_DESC
            );
        }

        $parameters = [
            'lock_recursive',
            'protect_feed',
            $redirect,
            'blog_admin_hint',
            'blog_admin_hint_text'
        ];

        return $this->formHelper->getSettingsForm($parameters);
    }

    /**
     * Returns the settings groups.
     *
     * @return array
     */
    public function getSettingsGroups()
    {
        return [
            self::GROUP_POST_TYPES,
            self::GROUP_TAXONOMIES,
            self::GROUP_FILES,
            self::GROUP_AUTHOR,
            self::GROUP_OTHER
        ];
    }

    /**
     * Returns the current settings form.
     *
     * @return \UserAccessManager\Form\Form[]
     */
    public function getCurrentGroupForms()
    {
        $group = $this->getCurrentSettingsGroup();
        $groupForms = [];

        if ($group === self::GROUP_POST_TYPES) {
            $groupForms[config::DEFAULT_TYPE] = $this->getPostSettingsForm();
            $postTypes = $this->getPostTypes();

            foreach ($postTypes as $postType => $postTypeObject) {
                if ($postType === ObjectHandler::ATTACHMENT_OBJECT_TYPE) {
                    continue;
                }

                $groupForms[$postType] = $this->getPostSettingsForm($postType);
            }
        } elseif ($group === self::GROUP_TAXONOMIES) {
            $groupForms[config::DEFAULT_TYPE] = $this->getTaxonomySettingsForm();
            $taxonomies = $this->getTaxonomies();

            foreach ($taxonomies as $taxonomy => $taxonomyObject) {
                if ($taxonomy === ObjectHandler::POST_FORMAT_TYPE) {
                    continue;
                }

                $groupForms[$taxonomy] = $this->getTaxonomySettingsForm($taxonomy);
            }
        } elseif ($group === self::GROUP_FILES) {
            $groupForms = [self::SECTION_FILES => $this->getFilesSettingsForm()];
        } elseif ($group === self::GROUP_AUTHOR) {
            $groupForms = [self::SECTION_AUTHOR => $this->getAuthorSettingsForm()];
        } elseif ($group === self::GROUP_OTHER) {
            $groupForms = [self::SECTION_OTHER => $this->getOtherSettingsForm()];
        }

        return $groupForms;
    }

    /**
     * Update settings action.
     */
    public function updateSettingsAction()
    {
        $this->verifyNonce('uamUpdateSettings');

        $newConfigParameters = $this->getRequestParameter('config_parameters');
        $this->config->setConfigParameters($newConfigParameters);

        if ($this->config->lockFile() === false) {
            $this->fileHandler->deleteFileProtection();
        } else {
            $this->fileHandler->createFileProtection();
        }

        $this->wordpress->doAction('uam_update_options', $this->config);
        $this->setUpdateMessage(TXT_UAM_UPDATE_SETTINGS);
    }

    /**
     * Checks if the group is a post type.
     *
     * @param string $key
     *
     * @return bool
     */
    public function isPostTypeGroup($key)
    {
        $postTypes = $this->getPostTypes();

        return isset($postTypes[$key]);
    }
}
