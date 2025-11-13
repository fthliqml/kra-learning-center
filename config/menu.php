<?php

return [
    'sidebar' => [
        [
            'id' => 'home',
            'label' => 'Home',
            'icon' => 'home',
            'href' => '/',
        ],
        [
            'id' => 'development',
            'label' => 'Development',
            'icon' => 'trophy',
            'href' => '/development',
        ],

        // Employee and Supervisor Menu Items
        [
            'id' => 'courses',
            'label' => 'Courses',
            'icon' => 'book-open',
            'href' => '/courses',
            'roles' => ['employee', 'spv'],
        ],
        [
            'id' => 'history',
            'label' => 'History',
            'icon' => 'clock',
            'href' => '#',
            'roles' => ['employee', 'spv'],
            'submenu' => [
                [
                    'label' => 'Training History',
                    'href' => '/history/training',
                    'roles' => ['employee', 'spv'],
                ],
                [
                    'label' => 'Certification History',
                    'href' => '/history/certification',
                    'roles' => ['employee', 'spv'],
                ],
            ],
        ],
        [
            'id' => 'training',
            'label' => 'Training',
            'icon' => 'academic-cap',
            'href' => '#',
            'roles' => ['employee', 'spv'],
            'submenu' => [
                ['label' => 'Training Schedule', 'href' => '/training/schedule', 'roles' => ['employee', 'spv']],
                ['label' => 'Training Request', 'href' => '/training/request', 'roles' => ['spv']],
            ],
        ],
        [
            'id' => 'survey',
            'label' => 'Survey',
            'icon' => 'document-text',
            'href' => '#',
            'roles' => ['employee', 'spv'],
            'submenu' => [
                ['label' => 'Survey 1', 'href' => '/survey/1', 'roles' => ['employee', 'spv']],
                ['label' => 'Survey 3', 'href' => '/survey/3', 'roles' => ['spv']],
            ],
        ],

        // Admin, Instructor, Certificator, and Leader Menu Items
        [
            'id' => 'training',
            'label' => 'Training',
            'icon' => 'academic-cap',
            'href' => '#',
            'roles' => ['admin', 'instructor', 'certificator', 'leader'],
            'submenu' => [
                ['label' => 'Training Module', 'href' => '/training/module', 'roles' => ['instructor', 'certificator', 'admin', 'leader']],
                ['label' => 'Training Schedule', 'href' => '/training/schedule', 'roles' => ['instructor', 'certificator', 'admin', 'leader']],
                ['label' => 'Training Request', 'href' => '/training/request', 'roles' => ['instructor', 'certificator', 'admin', 'leader']],
                ['label' => 'Training History', 'href' => '/history/training', 'roles' => ['tbc']],
                ['label' => 'Data Trainer', 'href' => '/training/trainer', 'roles' => ['instructor', 'certificator', 'admin', 'leader']],
            ],
        ],
        [
            'id' => 'certification',
            'label' => 'Certification',
            'icon' => 'check-badge',
            'href' => '#',
            'roles' => ['certificator', 'admin', 'leader'],
            'submenu' => [
                ['label' => 'Certification Module', 'href' => '/certification/module', 'roles' => ['certificator', 'admin', 'leader']],
            ],
        ],
        [
            'id' => 'k-learn',
            'label' => 'K-Learn',
            'icon' => 'folder-open',
            'href' => '/courses/management',
            'roles' => ['instructor', 'certificator', 'admin', 'leader'],
        ],
        [
            'id' => 'survey-management',
            'label' => 'Survey Management',
            'icon' => 'document-text',
            'href' => '#',
            'roles' => ['instructor', 'certificator', 'admin'],
            'submenu' => [
                ['label' => 'Survey 1', 'href' => '/survey/1/management', 'roles' => ['instructor', 'certificator', 'admin']],
                ['label' => 'Survey 2', 'href' => '/survey/2/management', 'roles' => ['instructor', 'certificator', 'admin']],
                ['label' => 'Survey 3', 'href' => '/survey/3/management', 'roles' => ['instructor', 'certificator', 'admin']],
            ],
        ],
        [
            'id' => 'survey-template',
            'label' => 'Survey Template',
            'icon' => 'document-duplicate',
            'href' => '/survey-template',
            'roles' => ['instructor', 'certificator', 'admin'],
        ],
    ],
    // Sidebar behavior options
    'flatten_child_when_parent_hidden' => true,
];
