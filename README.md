# Zepher

Add RBAC and fee-based SaaS versioning (e.g. Basic, Plus, Premium) to your codebase.

Zepher works by compiling configurations of RBAC and SaaS versioning into feature-based enforcement. You define
global assets and multiple applications at `zepher.io` and download the app-specific `zepher.json` file processing
against the codebase.

    if($zepher->userCanAccess('FEATURE_FOO', $userRoles)){

        // User with a role specified has access to the feature.
    }

    if($zepher->userCanAccess('FEATURE_FOO', $userRoles, 'PERMISSION_READ')){

        // User with a role and the permission specified has access to the feature.
    }

**The logic above will enforce role _and_ versioning restrictions.**

## Prerequisites

An account at https://zepher.io where you define requirements and download the object file. 

## Installation

This is the `PHP` version of the processor for the universal `zepher.json` object file.

Install it via composer:

    composer require deloachtech/zepher-php

Or download the package at https://github.com/deloachtech/zepher-php.


## Usage

See https://zepher.io for more information.

