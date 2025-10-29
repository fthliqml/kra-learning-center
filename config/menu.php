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
            'id' => 'courses',
            'label' => 'Courses',
            'icon' => 'book-open',
            'href' => '/courses',
            'roles' => ['employee', 'instructor'],
        ],
        [
            'id' => 'history',
            'label' => 'History',
            'icon' => 'clock',
            'href' => '#',
            'roles' => ['employee'],
            'submenu' => [
                [
                    'label' => 'Training History',
                    'href' => '/history/training',
                    'roles' => ['employee'],
                ],
                [
                    'label' => 'Certification History',
                    'href' => '/history/certification',
                    'roles' => ['employee'],
                ],
            ],
        ],
        [
            'id' => 'training-schedule',
            'label' => 'Training Schedule',
            'icon' => 'book-open',
            'href' => '/training/schedule',
            'roles' => ['employee'],
        ],
        [
            'id' => 'training',
            'label' => 'Training',
            'icon' => 'academic-cap',
            'href' => '#',
            'roles' => ['admin', 'instructor', 'leader'],
            'submenu' => [
                ['label' => 'Training History', 'href' => '/history/training', 'roles' => ['tbc']],
                ['label' => 'Training Module', 'href' => '/training/module', 'roles' => ['admin', 'instructor']],
                ['label' => 'Training Schedule', 'href' => '/training/schedule', 'roles' => ['admin', 'instructor']],
                ['label' => 'Data Trainer', 'href' => '/training/trainer', 'roles' => ['admin']],
            ],
        ],
        [
            'id' => 'k-learn',
            'label' => 'K-Learn',
            'icon' => 'folder-open',
            'href' => '/courses/management',
            'roles' => ['admin', 'instructor'],
        ],
        [
            'id' => 'survey-management',
            'label' => 'Survey Management',
            'icon' => 'document-text',
            'href' => '#',
            'roles' => ['admin', 'instructor'],
            'submenu' => [
                ['label' => 'Survey 1', 'href' => '/survey/1/management', 'roles' => ['admin', 'instructor']],
                ['label' => 'Survey 2', 'href' => '/survey/2/management', 'roles' => ['admin', 'instructor']],
                ['label' => 'Survey 3', 'href' => '/survey/3/management', 'roles' => ['admin', 'instructor']],
            ],
        ],
        [
            'id' => 'survey-template',
            'label' => 'Survey Template',
            'icon' => 'document-duplicate',
            'href' => '/survey-template',
            'roles' => ['admin', 'instructor'],
        ],
        [
            'id' => 'survey',
            'label' => 'Survey',
            'icon' => 'document-text',
            'href' => '#',
            'roles' => ['employee'],
            'submenu' => [
                ['label' => 'Survey 1', 'href' => '/survey/1', 'roles' => ['employee']],
                ['label' => 'Survey 2', 'href' => '/survey/2', 'roles' => ['employee']],
                ['label' => 'Survey 3', 'href' => '/survey/3', 'roles' => ['employee']],
            ],
        ],
        [
            'id' => 'development',
            'label' => 'Development',
            'icon' => 'trophy',
            'href' => '/development',
            'roles' => ['employee', 'admin', 'instructor'],
        ],
    ],
    // Sidebar behavior options
    'flatten_child_when_parent_hidden' => true,
];
