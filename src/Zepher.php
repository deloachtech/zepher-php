<?php
/**
 * This class has to be generic enough to user in multiple frameworks.
 * You can extend this class into a service and instantiate it there.
 * Then use the service to access these methods and add functionality.
 * You can also use the service as the data persistence class.
 */

namespace DeLoachTech\Zepher;

use Exception;

class Zepher
{
    protected $config;
    protected $versionId;
    protected $domainId;

    private $persistenceClass;

    /**
     * @param mixed $accountId The active account id (if any).
     * @param mixed $domainId The current account domain id.
     * @param object $persistenceClass Your class used to save account version information. (Must extend the PersistenceClassInterface)
     * @param string $configFileDirectory The directory (without the trailing slash) where your zepher.json and/or zepher_dev.json files exist.
     * @throws Exception
     */
    public function __construct(
        $accountId,
        ?string $domainId,
        object $persistenceClass,
        string $configFileDirectory = __DIR__
    )
    {
        $configFile = $configFileDirectory . DIRECTORY_SEPARATOR . 'zepher.json';
        $devFile = $configFileDirectory . DIRECTORY_SEPARATOR . 'zepher_dev.json';

        if (file_exists($devFile)) {
            $configFile = $devFile;
        } elseif (file_exists($configFile) == false) {
            throw new Exception('Unknown zepher config file ' . $configFile);
        }

        if ($persistenceClass instanceof PersistenceClassInterface) {

            $this->persistenceClass = $persistenceClass;
            $this->persistenceClass->setup($configFile, $accountId, $domainId);

            $this->domainId = $domainId;

            $this->config = json_decode(file_get_contents($configFile), true);

            if (isset($domainId) && count($this->config['data']['domains'][$domainId]['versions']) == 0) {
                throw new Exception('There are no versions assigned to domain "' . $domainId . '"');
            }

            if (isset($accountId)) {
                if (!$this->versionId = $this->getAccountVersionId($accountId)) {

                    // The account does not yet have a version assigned, so set the default. (The first of the sorted domain versions.)
                    $this->versionId = reset($this->config['data']['domains'][$domainId]['versions']);
                    $this->setAccountVersionId($accountId, $this->versionId);
                }
            }
        } else {
            throw new Exception('Persistence class must implement ' . __NAMESPACE__ . '\PersistenceClassInterface');
        }
    }


    /**
     * Returns an array of versions for current domain.
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
     * Returns the default version id for the current domain (or the domain id provided).
     * @return false|mixed
     */
    public function getDomainDefaultVersionId(string $domainId = null)
    {
        return reset($this->config['data']['domains'][$domainId ?? $this->domainId]['versions']); // The first version is the default
    }


    /**
     * Returns an array of the available signup domains.
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
     * Sets the account version id using the persistence class.
     * @param $accountId
     * @param string $versionId
     * @return bool
     * @throws Exception
     */
    public function setAccountVersionId($accountId, string $versionId): bool
    {
        if (!$this->persistenceClass->setVersionId($accountId, $versionId)) {
            throw new Exception('Failed to set account access version id.');
        }
        return true;
    }


    /**
     * Gets the account version id using the persistence class.
     * @param $accountId
     * @return string|null
     */
    public function getAccountVersionId($accountId): ?string
    {
        return $this->persistenceClass->getVersionId($accountId);
    }


    /**
     * Returns version data for the id provided.
     * @param string $versionId
     * @return array
     */
    public function getVersionById(string $versionId): array
    {
        return $this->config['data']['versions'][$versionId] ?? [];
    }


    /**
     * Returns the current domain data.
     * @return array
     */
    public function getDomain(): array
    {
        return $this->config['data']['domains'][$this->domainId] ?? [];
    }


    /**
     * Returns the current version data.
     * @return array
     */
    public function getVersion(): array
    {
        return $this->config['data']['versions'][$this->versionId] ?? [];
    }


    /**
     * Returns roles for the current version. Useful for providing
     * a list of roles to select from when managing users.
     * @return array
     */
    public function getRoles(): array
    {
        $roles = [];
        foreach ($this->config['data']['versions'][$this->versionId]['roles'] ?? [] as $id) {
            $roles[] = $this->config['data']['roles'][$id];
        }
        usort($roles, function ($item1, $item2) {
            return $item1['title'] <=> $item2['title'];
        });
        return $roles;
    }


    /**
     * Returns role titles for the provided ids, sorted by title. Useful
     * for providing a list of the roles a user is currently assigned.
     * @param array $roleIds
     * @return array [id => title]
     */
    public function getRolesById(array $roleIds): array
    {
        $roles = [];
        foreach ($roleIds as $roleId) {
            if (isset($this->config['data']['version'][$this->versionId]['roles'][$roleId])) {
                $roles[$roleId] = $this->config['data']['roles'][$roleId];
            }
        }
        asort($roles);
        return $roles;
    }


    /**
     * Returns an array of versions matching $tags sorted by $sortKey.
     * If $tags is an array, in_array() is used against version tag. If
     * $tags is a string, fnmatch() is used against version tag permitting
     * wildcards.
     *
     * This is a convenience method. It was used in earlier data structures
     * (before domains were introduced). There might be a use case for it.
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
        usort($a, function ($item1, $item2) use ($sortKey) {
            return $item1[$sortKey] <=> $item2[$sortKey];
        });
        return $a;
    }


    /**
     * Returns a bool indicating if the user has access to the feature with optional permission.
     * If no permission is provided, the method will return true if the user has at least one of
     * the roles associated with the feature.
     * @param string $feature
     * @param array $userRoles
     * @param string|null $permission
     * @return bool
     */
    public function userCanAccess(string $feature, array $userRoles, string $permission = null): bool
    {
        // TODO: Replace in_array() usage with more efficient logic. (This method is frequently called.)

        if (in_array($feature, $this->config['data']['versions'][$this->versionId]['features'])) {

            if ($permission == null) {

                // There's no permission set. Return true if the user has at least one of the roles.
                return count(array_intersect(array_keys($this->config['data']['access'][$feature]), $userRoles ?? [])) > 0;
            }

            foreach ($userRoles as $role) {
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
     * Method for determining if a module is active in the current environment.
     * @param string $moduleId
     * @return bool
     */
    public function moduleIsActive(string $moduleId): bool
    {
        return in_array($moduleId,$this->config['data']['versions'][$this->versionId]['modules']);
    }

}