<?php

return [
    'sidebar' => [
        // Home
        [
            'id' => 'home',
            'label' => 'Home',
            'icon' => 'home',
            'href' => '/',
        ],

        // Competency [LID]
        [
            'id' => 'competency',
            'label' => 'Competency',
            'icon' => 'archive-box',
            'href' => '/competency',
            'sections' => ['lid'],
            'roles' => ['admin', 'instructor', 'certificator'],
            'submenu' => [
                [
                    'label' => 'Competency Book',
                    'href' => '/competency/book',
                    'sections' => ['lid'],
                    'roles' => ['admin', 'instructor', 'certificator'],
                ],
                [
                    'label' => 'Competency Value',
                    'href' => '/competency/value',
                    'sections' => ['lid'],
                    'roles' => ['admin', 'instructor', 'certificator'],
                ],
                [
                    'label' => 'Competency Matrix',
                    'href' => '/competency/matrix',
                    'sections' => ['lid'],
                    'roles' => ['admin', 'instructor', 'certificator'],
                ],
            ],
        ],

        // Development [ALL]
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
                    'positions' => ['supervisor', 'section_head', 'department_head', 'division_head'],
                ],
                [
                    'label' => 'Development Recap',
                    'href' => '/development/recap',
                    'sections' => ['lid'],
                    'roles' => ['admin'],
                ],
            ],
        ],

        // Courses [Non LID]
        [
            'id' => 'courses',
            'label' => 'Courses',
            'icon' => 'book-open',
            'href' => '/courses',
            'exclude_sections' => ['lid'],
            'positions' => ['employee', 'supervisor', 'section_head', 'department_head', 'division_head'],
        ],


        // Training [Non LID]
        [
            'id' => 'training-employee',
            'label' => 'Training',
            'icon' => 'academic-cap',
            'href' => '#',
            'positions' => ['employee', 'supervisor', 'section_head', 'department_head', 'division_head'],
            'exclude_sections' => ['lid'],
            'submenu' => [
                [
                    'label' => 'Training Schedule',
                    'href' => '/training/schedule',
                    'positions' => ['employee', 'supervisor', 'section_head', 'department_head', 'division_head'],
                    'exclude_sections' => ['lid'],
                ],
                [
                    'label' => 'Training Test',
                    'href' => '/training-test',
                    'positions' => ['employee', 'supervisor', 'section_head', 'department_head', 'division_head'],
                    'exclude_sections' => ['lid'],
                ],
                [
                    'label' => 'Training Approval',
                    'href' => '/training/approval',
                    'positions' => ['department_head'],
                    'exclude_sections' => ['lid'],
                ],
                [
                    'label' => 'Training Request',
                    'href' => '/training/request',
                    'positions' => ['supervisor', 'department_head', 'division_head'],
                ],
            ],
        ],

        // Survey [Non LID]
        [
            'id' => 'survey',
            'label' => 'Survey',
            'icon' => 'document-text',
            'href' => '#',
            'positions' => ['employee', 'supervisor', 'section_head', 'department_head', 'division_head'],
            'exclude_sections' => ['lid'],
            'submenu' => [
                [
                    'label' => 'Survey 1',
                    'href' => '/survey/1',
                    'positions' => ['employee', 'supervisor', 'section_head', 'department_head', 'division_head'],
                    'exclude_sections' => ['lid'],
                ],
                [
                    'label' => 'Survey 3',
                    'href' => '/survey/3',
                    'positions' => ['supervisor', 'section_head', 'department_head', 'division_head'],
                    'exclude_sections' => ['lid'],
                ],
            ],
        ],

        // Training [LID]
        [
            'id' => 'training-admin',
            'label' => 'Training',
            'icon' => 'academic-cap',
            'href' => '#',
            'sections' => ['lid'],
            'positions' => ['section_head'],
            'roles' => ['admin', 'instructor', 'certificator'],
            'submenu' => [
                [
                    'label' => 'Trainer',
                    'href' => '/training/trainer',
                    'sections' => ['lid'],
                    'positions' => ['section_head'],
                    'roles' => ['instructor', 'certificator', 'admin'],
                ],
                [
                    'label' => 'Training Module',
                    'href' => '/training/module',
                    'sections' => ['lid'],
                    'positions' => ['section_head'],
                    'roles' => ['instructor', 'certificator', 'admin'],
                ],
                [
                    'label' => 'Training Schedule',
                    'href' => '/training/schedule',
                    'sections' => ['lid'],
                    'positions' => ['section_head'],
                    'roles' => ['instructor', 'certificator', 'admin'],
                ],
                [
                    'label' => 'Training Request',
                    'href' => '/training/request',
                    'sections' => ['lid'],
                    'positions' => ['section_head'],
                    'roles' => ['instructor', 'certificator', 'admin'],
                ],
                [
                    'label' => 'Training Approval',
                    'href' => '/training/approval',
                    'sections' => ['lid'],
                    'positions' => ['section_head'],
                ],
                [
                    'label' => 'Test Review',
                    'href' => '/test-review',
                    'sections' => ['lid'],
                    'positions' => ['section_head'],
                    'roles' => ['instructor', 'certificator',  'admin'],
                ],
            ],
        ],

        // Course Management [LID]
        [
            'id' => 'courses-management',
            'label' => 'Course',
            'icon' => 'folder-open',
            'href' => '/courses/management',
            'sections' => ['lid'],
            'positions' => ['section_head'],
            'roles' => ['multimedia', 'admin', 'instructor', 'certificator'],
        ],

        // Certification [LID]
        [
            'id' => 'certification',
            'label' => 'Certification',
            'icon' => 'check-badge',
            'href' => '#',
            'sections' => ['lid'],
            'positions' => ['section_head', 'department_head', 'division_head'],
            'roles' => ['certificator', 'admin'],
            'submenu' => [
                [
                    'label' => 'Certification Module',
                    'href' => '/certification/module',
                    'sections' => ['lid'],
                    'positions' => ['section_head'],
                    'roles' => ['certificator', 'admin'],
                ],
                [
                    'label' => 'Certification Schedule',
                    'href' => '/certification/schedule',
                    'sections' => ['lid'],
                    'positions' => ['section_head'],
                    'roles' => ['certificator', 'admin'],
                ],
                // [
                //     'label' => 'Certification Point',
                //     'href' => '/certification/point',
                //     'positions' => ['section_head'],
                //     'roles' => ['certificator', 'admin'],
                // ],
                [
                    'label' => 'Certification Approval',
                    'href' => '/certification/approval',
                    'sections' => ['lid'],
                    'positions' => ['section_head', 'department_head', 'division_head'],
                ],
            ],
        ],

        // Survey Management [LID]
        [
            'id' => 'survey',
            'label' => 'Survey',
            'icon' => 'document-text',
            'href' => '#',
            'sections' => ['lid'],
            'positions' => ['section_head'],
            'roles' => ['instructor', 'certificator', 'admin'],
            'submenu' => [
                [
                    'label' => 'Template',
                    'href' => '/survey-template',
                    'sections' => ['lid'],
                    'roles' => ['instructor', 'certificator', 'admin'],
                ],
                [
                    'label' => 'Survey 1',
                    'href' => '/survey/1/management',
                    'sections' => ['lid'],
                    'positions' => ['section_head'],
                    'roles' => ['instructor', 'certificator', 'admin'],
                ],
                [
                    'label' => 'Survey 3',
                    'href' => '/survey/3/management',
                    'sections' => ['lid'],
                    'positions' => ['section_head'],
                    'roles' => ['instructor', 'certificator', 'admin'],
                ],
            ],
        ],

        // History [ALL]
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

        // Reports [LID + Leader]
        [
            'id' => 'reports',
            'label' => 'Reports',
            'icon' => 'chart-bar',
            'href' => '#',
            'positions' => ['section_head', 'department_head', 'division_head'],
            'roles' => ['instructor', 'certificator', 'admin', 'multimedia'],
            'submenu' => [
                [
                    'label' => 'Training Activity',
                    'href' => '/reports/training-activity',
                    'positions' => ['section_head', 'department_head', 'division_head'],
                    'roles' => ['instructor', 'certificator', 'admin', 'multimedia'],
                ],
                [
                    'label' => 'Certification Activity',
                    'href' => '/reports/certification-activity',
                    'positions' => ['section_head', 'department_head', 'division_head'],
                    'roles' => ['instructor', 'certificator', 'admin', 'multimedia'],
                ],
                [
                    'label' => 'Instructor Daily Record',
                    'href' => '/reports/instructor-daily-record',
                    'sections' => ['lid'],
                    'positions' => ['section_head',],
                    'roles' => ['instructor', 'certificator', 'admin', 'multimedia'],
                ],
            ],
        ],
    ],

    // Sidebar behavior options
    'flatten_child_when_parent_hidden' => true,
];
