<?php
/**
 * This file is part of the deloachtech/zepher-php package.
 *
 * (c) DeLoach Tech, LLC
 * https://deloachtech.com
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


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

    private $devConfig = [];
    private $devMode = false;


    /**
     * @param string|null $domainId The current domain id.
     * @param mixed $accountId The active account id (if any).
     * @param array|null $userRoles The current user roles (if any).
     * @param object $persistenceClass Your class used to save account version information. (Must extend the PersistenceClassInterface)
     * @param string $objectFile The zepher JSON object file.
     * @throws Exception
     */
    public function __construct(
        ?string $domainId,
                $accountId,
        ?array  $userRoles,
        object  $persistenceClass,
        string  $objectFile
    )
    {

        if ($persistenceClass instanceof PersistenceClassInterface) {

            $info = pathinfo($objectFile);
            $dir = ($info['dirname'] ? $info['dirname'] . DIRECTORY_SEPARATOR : '');
            $devFile = $dir . $info['filename'] . '_dev.json';

            $this->domainId = $domainId;
            $this->userRoles = $userRoles;

            if (file_exists($devFile)) {

                $this->devMode = true;

                $this->devConfig = json_decode(file_get_contents($devFile), true);
                $this->domainId = $this->devConfig['simulate']['domain'];

                if (isset($this->devConfig['simulate']['role'])) {
                    $this->userRoles = (array)$this->devConfig['simulate']['role'];
                }
                $accountId = 'dev-mode';
            }

            $this->persistenceClass = $persistenceClass;
            $this->persistenceClass->objectFile($objectFile);


            $this->config = json_decode(file_get_contents($objectFile), true);

            if (isset($this->domainId) && count($this->config['data']['domains'][$this->domainId]['versions']) == 0) {
                throw new Exception('There are no versions assigned to domain "' . $this->domainId . '"');
            }

            if (isset($accountId)) {

                // There's a known account id (login is complete).

                $this->accessValueObject = new AccessValueObject($accountId);

                if ($this->devMode) {

                    if (empty($this->domainId)) {
                        throw new Exception("A domain id is required when in dev mode.");
                    }

                    // Initialize this->accessValueObject for dev mode.
                    $this->accessValueObject
                        ->setDomainId($this->domainId)
                        ->setActivated(time())
                        ->setVersionId($this->devConfig['simulate']['version'] ?? $this->getDomainDefaultVersionId($this->domainId));

                } else {

                    // Initialize this->accessValueObject.
                    $persistenceClass->getCurrentAccessRecord($this->accessValueObject);

                    if ($this->accessValueObject->getActivated() == null) {

                        // New account with no access record.

                        if (empty($this->domainId)) {
                            throw new Exception('There must be a domain id provided for a new account.');
                        }

                        $this->accessValueObject
                            ->setDomainId($this->domainId)
                            ->setActivated(time())
                            ->setVersionId($this->getDomainDefaultVersionId($this->domainId));

                        $this->createAccessRecord($this->accessValueObject);

                    } else {

                        // Existing account...

                        $this->domainId = $this->accessValueObject->getDomainId(); // In case one was not available during initialization.

                    }

                }
            }
        } else {
            throw new Exception('Persistence class must implement ' . __NAMESPACE__ . '\PersistenceClassInterface');
        }
    }


    /**
     * Returns an array of versions for current domain (or the domain id provided).
     *
     * @return array
     */
    public function getDomainVersions(string $domainId = null): array
    {
        $versions = [];
        foreach ($this->config['data']['domains'][$domainId ?? $this->domainId]['versions'] as $id) {
            $versions[$id] = $this->config['data']['versions'][$id];
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
     * @param AccessValueObject $accessValueObject
     * @return void
     * @throws Exception
     */
    public function createAccessRecord(AccessValueObject $accessValueObject)
    {
        if (!$this->devMode) {
            if (!$this->persistenceClass->createAccessRecord($accessValueObject)) {
                throw new Exception('Failed to create access record.');
            }
        }
    }

    /**
     * @param AccessValueObject $accessValueObject
     * @return void
     * @throws Exception
     */
    public function updateAccessRecord(AccessValueObject $accessValueObject)
    {
        if (!$this->devMode) {
            if ($accessValueObject->getVersionId() != $this->accessValueObject->getVersionId()) {
                $this->createAccessRecord($accessValueObject);
            } else {
                if (!$this->persistenceClass->updateAccessRecord($accessValueObject)) {
                    throw new Exception('Failed to update access record.');
                }
            }
        }
    }


    /**
     * Returns the current AccessValueObject.
     * @return AccessValueObject
     */
    public function getAccessValueObject(): AccessValueObject
    {
        return $this->accessValueObject;
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
        if ($data = $this->config['data']['versions'][$versionId] ?? []) {
            return [$versionId => $data];
        }
        return [];
    }


    /**
     * Returns domain data for the current domain (or the domain id provided).
     *
     * @return array
     */
    public function getDomain(string $domainId = null): array
    {
        if ($data = $this->config['data']['domains'][$domainId ?? $this->domainId] ?? []) {
            return [$domainId => $data];
        }
        return [];
    }


    /**
     * Returns the network of the current domain  (or the domain id provided).
     *
     * @return array
     */
    public function getDomainNetwork(string $domainId = null): array
    {
        $domains = [];

        foreach ($this->config['data']['domains'][$domainId ?? $this->domainId]['network'] ?? [] as $id) {

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
        if ($data = $this->config['data']['versions'][$this->accessValueObject->getVersionId()] ?? []) {
            return [$this->accessValueObject->getVersionId() => $data];
        }
        return [];
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
            $roles[$id] = $this->config['data']['roles'][$id];
        }
        uasort($roles, function ($a, $b) {
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
        $haystack = array_flip($this->config['data']['versions'][$this->accessValueObject->getVersionId()]['roles'] ?? []);
        foreach ($roleIds as $roleId) {
            if (isset($haystack[$roleId])) {
                $roles[$roleId] = $this->config['data']['roles'][$roleId]['title'];
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
        uasort($a, function ($a, $b) use ($sortKey) {
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
        // TODO: Replace all in_array() usage with more efficient logic. (This method gets called frequently.)

        if (!$this->accessValueObject) {
            return false;
        }

        if (isset($this->devConfig['features_deny'])) {
            if (in_array('*', $this->devConfig['features_deny']) || in_array($feature, $this->devConfig['features_deny'])) {
                return false;
            }
        }

        if (in_array($feature, $this->config['data']['versions'][$this->accessValueObject->getVersionId()]['features']??[])) {

            // Application logic requiring non-existent features may error.
            // Make sure the feature even exists before granting dev permission.
            if (isset($this->devConfig['feature_access']['*'])) {
                return ($this->devConfig['feature_access']['*'] == '*' || in_array($this->devConfig['feature_access']['*'], $this->userRoles));
            } elseif (isset($this->devConfig['feature_access'][$feature])) {
                return ($this->devConfig['feature_access'][$feature] == '*' || in_array($this->devConfig['feature_access'][$feature], $this->userRoles));
            }


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
        foreach ($this->config['data']['access'][$featureId] as $roleId => $permissions) {
            if (in_array($roleId, $roleIds)) {
                foreach ($permissions as $permissionId) {
                    $ret[$permissionId] = 1;
                }
            }
        }
        ksort($ret);
        return array_keys($ret);
    }
}