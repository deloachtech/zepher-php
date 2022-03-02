<?php
/**
 * The zepher.json data processing class.
 *
 * Although fully functional, this class is provided to get things started. You can roll your own, or extend this one
 * into a service and add/modify functionality.
 */

namespace DeLoachTech\Zepher;

use Exception;

class Zepher
{
    protected $config;
    protected $domainId;
    protected $accessValueObject;

    private $persistenceClass;
    private $userRoles;


    /**
     * @param string|null $domainId The current domain id.
     * @param mixed $accountId The active account id (if any).
     * @param array|null $userRoles The current user roles (if any).
     * @param object $persistenceClass Your class used to save account version information. (Must extend the PersistenceClassInterface)
     * @param string $objectFile The zepher.json file.
     * @throws Exception
     */
    public function __construct(
        ?string $domainId,
        $accountId,
        ?array $userRoles,
        object $persistenceClass,
        string $objectFile
    )
    {
        $configFile = self::getObjectFile($objectFile);
//        $info = pathinfo($objectFile);
//        $configFile = $info['dirname'] . DIRECTORY_SEPARATOR . 'zepher.json';
//        $devFile = $info['dirname'] . DIRECTORY_SEPARATOR . 'zepher_dev.json';
//
//        if (file_exists($devFile)) {
//            $configFile = $devFile;
//        } elseif (file_exists($configFile) == false) {
//            throw new Exception('Unknown zepher object file ' . $configFile);
//        }


        if ($persistenceClass instanceof PersistenceClassInterface) {

            $this->persistenceClass = $persistenceClass;
            $this->persistenceClass->configFile($configFile);

            $this->domainId = $domainId;
            $this->userRoles = $userRoles;

            $this->config = json_decode(file_get_contents($configFile), true);

            if (isset($domainId) && count($this->config['data']['domains'][$domainId]['versions']) == 0) {
                throw new Exception('There are no versions assigned to domain "' . $domainId . '"');
            }

            if (isset($accountId)) {

                $this->accessValueObject = new AccessValueObject($accountId);
                $persistenceClass->getAccessValues($this->accessValueObject);

                if ($this->accessValueObject->getVersionId() == null) {

                    // The account does not yet have a version assigned, so set the default.
                    $this->accessValueObject
                        ->setVersionId($this->getDomainDefaultVersionId($domainId))
                        ->setActivated(time());

                    $this->updateValueObject($this->accessValueObject);
                }
            }
        } else {
            throw new Exception('Persistence class must implement ' . __NAMESPACE__ . '\PersistenceClassInterface');
        }
    }


    public static function getObjectFile($objectFile): string
    {
        $info = pathinfo($objectFile);
        $file = $info['dirname'] . DIRECTORY_SEPARATOR . 'zepher.json';
        $devFile = $info['dirname'] . DIRECTORY_SEPARATOR . 'zepher_dev.json';

        if (file_exists($devFile)) {
            $file = $devFile;
        } elseif (file_exists($file) == false) {
            throw new Exception('Unknown zepher object file ' . $file);
        }
        return $file;
    }

    /**
     * Returns an array of versions for current domain.
     *
     * @return array
     */
    public function getDomainVersions(): array
    {
        $versions = [];
        foreach ($this->config['data']['domains'][$this->domainId]['versions'] as $id) {
            $versions[] = $this->config['data']['versions'][$id];
        }
        return $versions;
    }


    /**
     * Returns the default version id for the current domain (or the domain id provided). The default version is the first
     * version in a group of versions (index 0).
     *
     * @return false|mixed
     */
    public function getDomainDefaultVersionId(string $domainId = null)
    {
        return reset($this->config['data']['domains'][$domainId ?? $this->domainId]['versions']);
    }


    /**
     * Returns an array of the available signup domains.
     *
     * @return array
     */
    public function getSignupDomains(): array
    {
        $signupDomains = [];
        $domains = $this->config['data']['domains'] ?? [];

        foreach ($domains as $id => $v) {
            if ($v['signup'] == true) {
                $signupDomains[$id] = $v;
            }
        }
        return $signupDomains;
    }


    /**
     * Passes the persistence class values for saving.
     *
     * @param AccessValueObject $accessValueObject
     * @return void
     * @throws Exception
     */
    private function updateValueObject(AccessValueObject $accessValueObject)
    {
        if (!$this->persistenceClass->setAccessValues($accessValueObject)) {
            throw new Exception('Failed to save access value object.');
        }
    }


    /**
     * Sets the account version id using the persistence class.
     *
     * @param string $versionId
     * @return bool
     * @throws Exception
     */
    public function setAccountVersionId(string $versionId): bool
    {
        $this->accessValueObject
            ->setVersionId($versionId)
            ->setActivated(time());
        if (!$this->persistenceClass->setAccessValues($this->accessValueObject)) {
            throw new Exception('Failed to set account access version id.');
        }
        return true;
    }


    /**
     * Gets the account version id from the current AccessValueObject.
     *
     * @return string|null
     */
    public function getAccountVersionId(): ?string
    {
        return $this->accessValueObject->getVersionId();
    }


    /**
     * Returns version data for the id provided.
     *
     * @param string $versionId
     * @return array
     */
    public function getVersionById(string $versionId): array
    {
        return $this->config['data']['versions'][$versionId] ?? [];
    }


    /**
     * Returns the current domain data.
     *
     * @return array
     */
    public function getDomain(): array
    {
        return $this->config['data']['domains'][$this->domainId] ?? [];
    }


    /**
     * Returns the current domain network.
     *
     * @return array
     */
    public function getDomainNetwork(): array
    {
        $domains = [];

        foreach ($this->config['data']['domains'][$this->domainId]['network'] ?? [] as $id) {

            $domains[$id] = [
                'id' => $id,
                'title' => $this->config['data']['domains'][$id]['title']
            ];
        }

        return $domains;
    }


    /**
     * Returns the current version data.
     *
     * @return array
     */
    public function getVersion(): array
    {
        return $this->config['data']['versions'][$this->accessValueObject->getVersionId()] ?? [];
    }


    /**
     * Returns roles for the current version. Useful for providing a list of roles to select from when managing users.
     *
     * @return array
     */
    public function getRoles(): array
    {
        $roles = [];
        foreach ($this->config['data']['versions'][$this->accessValueObject->getVersionId()]['roles'] ?? [] as $id) {
            $roles[] = $this->config['data']['roles'][$id];
        }
        usort($roles, function ($a, $b) {
            return $a['title'] <=> $b['title'];
        });
        return $roles;
    }


    /**
     * Returns role titles for the provided ids, sorted by title. Useful for providing a list of the roles a user is
     * currently assigned.
     *
     * @param array $roleIds
     * @return array [id => title]
     */
    public function getRolesById(array $roleIds): array
    {
        $roles = [];
        foreach ($roleIds as $roleId) {
            if (isset($this->config['data']['version'][$this->accessValueObject->getVersionId()]['roles'][$roleId])) {
                $roles[$roleId] = $this->config['data']['roles'][$roleId];
            }
        }
        asort($roles);
        return $roles;
    }


    /**
     * Returns an array of versions matching $tags sorted by $sortKey. If $tags is an array, in_array() is used against
     * version tag. If $tags is a string, fnmatch() is used against version tag permitting wildcards.
     *
     * This is a convenience method. It was used in earlier data structures (before domains were introduced). There might
     * be a use case for it, so it remains here. (It's also one of those functions you hate to delete.)
     *
     * See https://www.php.net/manual/en/function.fnmatch.php for more information.
     *
     * @param mixed $tags
     * @param string $sortKey Default is 'tag'
     * @return array
     */
    public function getTaggedVersions($tags, string $sortKey = 'tag'): array
    {
        $a = [];
        foreach ($this->config['data']['versions'] ?? [] as $k => $v) {
            if (is_array($tags)) {
                if (in_array($v['tag'], $tags)) {
                    $a[$k] = $v;
                }
            } else {
                if (fnmatch($tags, $v['tag'])) {
                    $a[$k] = $v;
                }
            }
        }
        usort($a, function ($a, $b) use ($sortKey) {
            return $a[$sortKey] <=> $b[$sortKey];
        });
        return $a;
    }


    /**
     * Returns a bool indicating if the user has access to the feature with optional permission. If no permission is
     * provided, the method will return true if the user has at least one of the roles associated with the feature.
     *
     * @param string $feature
     * @param string|null $permission
     * @return bool
     */
    public function userCanAccess(string $feature, string $permission = null): bool
    {
        if (!$this->accessValueObject) {
            return false;
        }

        // TODO: Replace in_array() usage with more efficient logic. (This method is called frequently.)

        if (in_array($feature, $this->config['data']['versions'][$this->accessValueObject->getVersionId()]['features'])) {

            if ($permission == null) {

                // There's no permission set. Return true if the user has at least one of the roles.
                return count(array_intersect(array_keys($this->config['data']['access'][$feature]), $this->userRoles ?? [])) > 0;
            }

            foreach ($this->userRoles as $role) {
                if (!empty($this->config['data']['access'][$feature][$role])) {
                    if (
                        in_array($permission, $this->config['data']['access'][$feature][$role]) ||
                        in_array($this->config['data']['app']['permission_all'] ?? [], $this->config['data']['access'][$feature][$role])
                    ) {
                        return true;
                    }
                }
            }
        }
        return false;
    }


    /**
     * Returns an array of feature permissions assigned for every user role.
     *
     * @param string $featureId
     * @param array $roleIds
     * @return array
     */
    public function getUserFeaturePermissions(string $featureId, array $roleIds): array
    {
        $ret = [];
        foreach ($this->config['data']['access'][$featureId] as $roleId => $arr) {
            if (in_array($roleId, $roleIds)) {
                foreach ($arr as $permissionId) {
                    $ret[$permissionId] = 1;
                }
            }
        }
        ksort($ret);
        return array_keys($ret);
    }


    /**
     * Returns an array of module ids associated with a domain.
     *
     * @return array
     */
    public function getDomainModules(): array
    {
        return $this->config['data']['versions'][$this->accessValueObject->getVersionId()]['modules'] ?? [];
    }


    /**
     * Method for determining if a module is active in the current environment. Useful for determining module related
     * events and information (i.e. tips and alerts).
     *
     * @param string $moduleId
     * @return bool
     */
    public function moduleIsActive(string $moduleId): bool
    {
        return in_array($moduleId, $this->config['data']['versions'][$this->accessValueObject->getVersionId()]['modules']);
    }
}