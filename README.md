# Zepher

Add RBAC and fee-based SaaS versioning (e.g. Basic, Plus, Premium) to your codebase.

Zepher works by compiling complex configurations of RBAC and SaaS versioning into feature-based enforcement. You define
global assets and multiple applications at `zepher.io` and download the compiled `zepher.json` file for app-specific results.

    if($zepher->userCanAccess('FEATURE_FOO', $userRoles)){

        // User with a role specified has access to the feature.
    }

    if($zepher->userCanAccess('FEATURE_FOO', $userRoles, 'PERMISSION_READ')){

        // User with a role and the permission specified has access to the feature.
    }

**The logic above will enforce role _and_ versioning restrictions.**

See https://zepher.io for more information.

## Prerequisites

An account at https://zepher.io where you define requirements and download the object file. 

## Installation

This is the `PHP` version of the processor for the universal `zepher.json` object file.

Install it via composer:

    composer require deloachtech/zepher

Or download the package at https://github.com/deloachtech/zepher.


## Usage

Instantiate the Zepher class and begin. An interface is provided for incorporating your data persistence.

    $zepher = new Zepher($accountId, $domainId, new FilesystemPersistenceClass(), $configDir);


    // To offer domain version selection
    $versions = $zepher->getDomainVersions();


    // Or during signup
    $versions = $zepher->getSignupDomains();


    // To assign user roles
    $roles = $zepher->getRoles();


    // To enforce everything (RBAC and domain versioning)
    if($zepher->userCanAccess('SOME_FEATURE', $userRoles, 'SOME_PERMISSION')){
        //...
    }

Using your existing logic, there are three variables from the `zepher.json` file for account assignment.

1. Domain (e.g. Bank, Hospital).
2. Domain version (e.g. Basic, Plus, Premium).
3. User roles (e.g. Admin, Super, Clerk).


See https://docs.zepher.io for more information.

