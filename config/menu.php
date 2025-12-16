<?php

return [
    'sidebar' => [
        // Home - All users
        [
            'id' => 'home',
            'label' => 'Home',
            'icon' => 'home',
            'href' => '/',
        ],

        // Competency - Admin, Leader
        [
            'id' => 'competency',
            'label' => 'Competency',
            'icon' => 'archive-box',
            'href' => '/competency',
            'roles' => ['admin', 'leader'],
            'submenu' => [
                [
                    'label' => 'Competency Book',
                    'href' => '/competency/book',
                    'roles' => ['admin', 'leader'],
                ],
                [
                    'label' => 'Competency Value',
                    'href' => '/competency/value',
                    'roles' => ['admin', 'leader'],
                ],
                [
                    'label' => 'Competency Matrix',
                    'href' => '/competency/matrix',
                    'roles' => ['admin', 'leader'],
                ],
            ],
        ],

        // Development - All users
        [
            'id' => 'development',
            'label' => 'Development',
            'icon' => 'trophy',
            'href' => '/development',
            'submenu' => [
                [
                    'label' => 'Development Plan',
                    'href' => '/development/plan',
                ],
                [
                    'label' => 'Development Approval',
                    'href' => '/development/approval',
                    'roles' => ['spv', 'leader'],
                ],
            ],
        ],

        // Courses - Employee and Supervisor
        [
            'id' => 'courses',
            'label' => 'Courses',
            'icon' => 'book-open',
            'href' => '/courses',
            'roles' => ['employee', 'spv'],
        ],

        // Training - Employee and Supervisor
        [
            'id' => 'training-employee',
            'label' => 'Training',
            'icon' => 'academic-cap',
            'href' => '#',
            'roles' => ['employee', 'spv'],
            'submenu' => [
                [
                    'label' => 'Training Schedule',
                    'href' => '/training/schedule',
                    'roles' => ['employee', 'spv'],
                ],
                [
                    'label' => 'Training Request',
                    'href' => '/training/request',
                    'roles' => ['spv'],
                ],
            ],
        ],

        // Survey - Employee and Supervisor
        [
            'id' => 'survey',
            'label' => 'Survey',
            'icon' => 'document-text',
            'href' => '#',
            'roles' => ['employee', 'spv'],
            'submenu' => [
                [
                    'label' => 'Survey 1',
                    'href' => '/survey/1',
                    'roles' => ['employee', 'spv'],
                ],
                [
                    'label' => 'Survey 3',
                    'href' => '/survey/3',
                    'roles' => ['spv'],
                ],
            ],
        ],

        // Training - Admin, Instructor, Certificator, Leader
        [
            'id' => 'training-admin',
            'label' => 'Training',
            'icon' => 'academic-cap',
            'href' => '#',
            'roles' => ['admin', 'instructor', 'certificator', 'leader'],
            'submenu' => [
                [
                    'label' => 'Trainer',
                    'href' => '/training/trainer',
                    'roles' => ['instructor', 'certificator', 'admin', 'leader'],
                ],
                [
                    'label' => 'Training Module',
                    'href' => '/training/module',
                    'roles' => ['instructor', 'certificator', 'admin', 'leader'],
                ],
                [
                    'label' => 'Training Schedule',
                    'href' => '/training/schedule',
                    'roles' => ['instructor', 'certificator', 'admin', 'leader'],
                ],
                [
                    'label' => 'Training Request',
                    'href' => '/training/request',
                    'roles' => ['instructor', 'certificator', 'admin', 'leader'],
                ],
                [
                    'label' => 'Training Approval',
                    'href' => '/training/approval',
                    'roles' => ['leader'],
                ],
            ],
        ],

        // Course Management - Admin, Instructor, Certificator, Leader
        [
            'id' => 'courses-management',
            'label' => 'Course',
            'icon' => 'folder-open',
            'href' => '/courses/management',
            'roles' => ['instructor', 'certificator', 'admin', 'leader'],
        ],

        // Certification - Certificator, Admin, Leader
        [
            'id' => 'certification',
            'label' => 'Certification',
            'icon' => 'check-badge',
            'href' => '#',
            'roles' => ['certificator', 'admin', 'leader'],
            'submenu' => [
                [
                    'label' => 'Certification Module',
                    'href' => '/certification/module',
                    'roles' => ['certificator', 'admin', 'leader'],
                ],
                [
                    'label' => 'Certification Schedule',
                    'href' => '/certification/schedule',
                    'roles' => ['certificator', 'admin', 'leader'],
                ],
                [
                    'label' => 'Certification Point',
                    'href' => '/certification/point',
                    'roles' => ['certificator', 'admin', 'leader'],
                ],
                [
                    'label' => 'Certification Approval',
                    'href' => '/certification/approval',
                    'roles' => ['leader'],
                ],
            ],
        ],

        // Survey Management - Admin, Instructor, Certificator
        [
            'id' => 'survey',
            'label' => 'Survey',
            'icon' => 'document-text',
            'href' => '#',
            'roles' => ['instructor', 'certificator', 'admin'],
            'submenu' => [
                [
                    'label' => 'Template',
                    'href' => '/survey-template',
                    'roles' => ['instructor', 'certificator', 'admin'],
                ],
                [
                    'label' => 'Survey 1',
                    'href' => '/survey/1/management',
                    'roles' => ['instructor', 'certificator', 'admin'],
                ],
                [
                    'label' => 'Survey 3',
                    'href' => '/survey/3/management',
                    'roles' => ['instructor', 'certificator', 'admin'],
                ],
            ],
        ],

        // History - All users
        [
            'id' => 'history',
            'label' => 'History',
            'icon' => 'clock',
            'href' => '#',
            'submenu' => [
                [
                    'label' => 'Training History',
                    'href' => '/training/history',
                ],
                [
                    'label' => 'Certification History',
                    'href' => '/certification/history',
                ],
            ],
        ],

        // Reports - Admin, Instructor, Certificator, Leader
        [
            'id' => 'reports',
            'label' => 'Reports',
            'icon' => 'chart-bar',
            'href' => '#',
            'roles' => ['instructor', 'certificator', 'admin', 'leader'],
            'submenu' => [
                [
                    'label' => 'Training Activity',
                    'href' => '/reports/training-activity',
                    'roles' => ['instructor', 'certificator', 'admin', 'leader'],
                ],
                [
                    'label' => 'Certification Activity',
                    'href' => '/reports/certification-activity',
                    'roles' => ['instructor', 'certificator', 'admin', 'leader'],
                ],
            ],
        ],
    ],

    // Sidebar behavior options
    'flatten_child_when_parent_hidden' => true,
];
