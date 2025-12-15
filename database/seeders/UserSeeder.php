<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Sample users based on actual organizational structure
        $users = [
            // Board of Commissioner
            [
                'name' => 'Commissioner',
                'nrp' => 9999,
                'email' => 'commissioner@example.com',
                'password' => Hash::make('password'),
                'section' => null,
                'department' => null,
                'division' => 'Board of Commissioner',
                'position' => 'director',
            ],
            // Board of Director
            [
                'name' => 'Director',
                'nrp' => 9001,
                'email' => 'director@example.com',
                'password' => Hash::make('password'),
                'section' => null,
                'department' => null,
                'division' => 'Board of Director',
                'position' => 'director',
            ],

            // ========== Human Capital, Finance & General Support Division ==========
            // Department: Human Capital, General Service, Security & LID
            // Section: LID (tidak diubah sesuai instruksi user)
            [
                'name' => 'LID Employee 1',
                'nrp' => 1001,
                'email' => 'employee@example.com',
                'password' => Hash::make('password'),
                'section' => 'LID',
                'department' => 'Human Capital, General Service, Security & LID',
                'division' => 'Human Capital, Finance & General Support',
                'position' => 'employee',
            ],
            [
                'name' => 'LID Employee 2',
                'nrp' => 1002,
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
                'section' => 'LID',
                'department' => 'Human Capital, General Service, Security & LID',
                'division' => 'Human Capital, Finance & General Support',
                'position' => 'employee',
            ],
            [
                'name' => 'LID Employee 3',
                'nrp' => 1003,
                'email' => 'instructor@example.com',
                'password' => Hash::make('password'),
                'section' => 'LID',
                'department' => 'Human Capital, General Service, Security & LID',
                'division' => 'Human Capital, Finance & General Support',
                'position' => 'employee',
            ],
            [
                'name' => 'LID Employee 4',
                'nrp' => 1004,
                'email' => 'certificator@example.com',
                'password' => Hash::make('password'),
                'section' => 'LID',
                'department' => 'Human Capital, General Service, Security & LID',
                'division' => 'Human Capital, Finance & General Support',
                'position' => 'employee',
            ],
            [
                'name' => 'LID Section Head',
                'nrp' => 1005,
                'email' => 'lid_section_head@example.com',
                'password' => Hash::make('password'),
                'section' => 'LID',
                'department' => 'Human Capital, General Service, Security & LID',
                'division' => 'Human Capital, Finance & General Support',
                'position' => 'section_head',
            ],

            // Section: Human Capital
            [
                'name' => 'Human Capital Employee 1',
                'nrp' => 1011,
                'email' => 'hc_employee_1@example.com',
                'password' => Hash::make('password'),
                'section' => 'Human Capital',
                'department' => 'Human Capital, General Service, Security & LID',
                'division' => 'Human Capital, Finance & General Support',
                'position' => 'employee',
            ],
            [
                'name' => 'Human Capital Employee 2',
                'nrp' => 1012,
                'email' => 'hc_employee_2@example.com',
                'password' => Hash::make('password'),
                'section' => 'Human Capital',
                'department' => 'Human Capital, General Service, Security & LID',
                'division' => 'Human Capital, Finance & General Support',
                'position' => 'employee',
            ],
            [
                'name' => 'Human Capital Section Head',
                'nrp' => 1013,
                'email' => 'hc_section_head@example.com',
                'password' => Hash::make('password'),
                'section' => 'Human Capital',
                'department' => 'Human Capital, General Service, Security & LID',
                'division' => 'Human Capital, Finance & General Support',
                'position' => 'section_head',
            ],

            // Section: General Service
            [
                'name' => 'General Service Employee 1',
                'nrp' => 1021,
                'email' => 'gs_employee_1@example.com',
                'password' => Hash::make('password'),
                'section' => 'General Service',
                'department' => 'Human Capital, General Service, Security & LID',
                'division' => 'Human Capital, Finance & General Support',
                'position' => 'employee',
            ],
            [
                'name' => 'General Service Employee 2',
                'nrp' => 1022,
                'email' => 'gs_employee_2@example.com',
                'password' => Hash::make('password'),
                'section' => 'General Service',
                'department' => 'Human Capital, General Service, Security & LID',
                'division' => 'Human Capital, Finance & General Support',
                'position' => 'employee',
            ],
            [
                'name' => 'General Service Section Head',
                'nrp' => 1023,
                'email' => 'gs_section_head@example.com',
                'password' => Hash::make('password'),
                'section' => 'General Service',
                'department' => 'Human Capital, General Service, Security & LID',
                'division' => 'Human Capital, Finance & General Support',
                'position' => 'section_head',
            ],

            // Section: Security
            [
                'name' => 'Security Employee 1',
                'nrp' => 1031,
                'email' => 'security_employee_1@example.com',
                'password' => Hash::make('password'),
                'section' => 'Security',
                'department' => 'Human Capital, General Service, Security & LID',
                'division' => 'Human Capital, Finance & General Support',
                'position' => 'employee',
            ],
            [
                'name' => 'Security Employee 2',
                'nrp' => 1032,
                'email' => 'security_employee_2@example.com',
                'password' => Hash::make('password'),
                'section' => 'Security',
                'department' => 'Human Capital, General Service, Security & LID',
                'division' => 'Human Capital, Finance & General Support',
                'position' => 'employee',
            ],
            [
                'name' => 'Security Section Head',
                'nrp' => 1033,
                'email' => 'security_section_head@example.com',
                'password' => Hash::make('password'),
                'section' => 'Security',
                'department' => 'Human Capital, General Service, Security & LID',
                'division' => 'Human Capital, Finance & General Support',
                'position' => 'section_head',
            ],

            // Department Head
            [
                'name' => 'HC Dept Head',
                'nrp' => 1090,
                'email' => 'hc_dept_head@example.com',
                'password' => Hash::make('password'),
                'section' => null,
                'department' => 'Human Capital, General Service, Security & LID',
                'division' => 'Human Capital, Finance & General Support',
                'position' => 'department_head',
            ],

            // Department: Finance & Accounting
            // Section: Finance
            [
                'name' => 'Finance Employee 1',
                'nrp' => 1101,
                'email' => 'finance_employee_1@example.com',
                'password' => Hash::make('password'),
                'section' => 'Finance',
                'department' => 'Finance & Accounting',
                'division' => 'Human Capital, Finance & General Support',
                'position' => 'employee',
            ],
            [
                'name' => 'Finance Employee 2',
                'nrp' => 1102,
                'email' => 'finance_employee_2@example.com',
                'password' => Hash::make('password'),
                'section' => 'Finance',
                'department' => 'Finance & Accounting',
                'division' => 'Human Capital, Finance & General Support',
                'position' => 'employee',
            ],
            [
                'name' => 'Finance Section Head',
                'nrp' => 1103,
                'email' => 'finance_section_head@example.com',
                'password' => Hash::make('password'),
                'section' => 'Finance',
                'department' => 'Finance & Accounting',
                'division' => 'Human Capital, Finance & General Support',
                'position' => 'section_head',
            ],

            // Section: Accounting
            [
                'name' => 'Accounting Employee 1',
                'nrp' => 1111,
                'email' => 'accounting_employee_1@example.com',
                'password' => Hash::make('password'),
                'section' => 'Accounting',
                'department' => 'Finance & Accounting',
                'division' => 'Human Capital, Finance & General Support',
                'position' => 'employee',
            ],
            [
                'name' => 'Accounting Employee 2',
                'nrp' => 1112,
                'email' => 'accounting_employee_2@example.com',
                'password' => Hash::make('password'),
                'section' => 'Accounting',
                'department' => 'Finance & Accounting',
                'division' => 'Human Capital, Finance & General Support',
                'position' => 'employee',
            ],
            [
                'name' => 'Accounting Section Head',
                'nrp' => 1113,
                'email' => 'accounting_section_head@example.com',
                'password' => Hash::make('password'),
                'section' => 'Accounting',
                'department' => 'Finance & Accounting',
                'division' => 'Human Capital, Finance & General Support',
                'position' => 'section_head',
            ],

            // Department Head
            [
                'name' => 'Finance & Accounting Dept Head',
                'nrp' => 1190,
                'email' => 'finance_dept_head@example.com',
                'password' => Hash::make('password'),
                'section' => null,
                'department' => 'Finance & Accounting',
                'division' => 'Human Capital, Finance & General Support',
                'position' => 'department_head',
            ],

            // Division Head
            [
                'name' => 'HC Finance Division Head',
                'nrp' => 1900,
                'email' => 'hcfin_div_head@example.com',
                'password' => Hash::make('password'),
                'section' => null,
                'department' => null,
                'division' => 'Human Capital, Finance & General Support',
                'position' => 'division_head',
            ],

            // ========== Strategic & IT Development Division ==========
            // Department: Digitalization & IT
            // Section: IT
            [
                'name' => 'IT Employee 1',
                'nrp' => 2001,
                'email' => 'it_employee_1@example.com',
                'password' => Hash::make('password'),
                'section' => 'IT',
                'department' => 'Digitalization & IT',
                'division' => 'Strategic & IT Development',
                'position' => 'employee',
            ],
            [
                'name' => 'IT Employee 2',
                'nrp' => 2002,
                'email' => 'it_employee_2@example.com',
                'password' => Hash::make('password'),
                'section' => 'IT',
                'department' => 'Digitalization & IT',
                'division' => 'Strategic & IT Development',
                'position' => 'employee',
            ],
            [
                'name' => 'IT Section Head',
                'nrp' => 2003,
                'email' => 'it_section_head@example.com',
                'password' => Hash::make('password'),
                'section' => 'IT',
                'department' => 'Digitalization & IT',
                'division' => 'Strategic & IT Development',
                'position' => 'section_head',
            ],

            // Section: Digitalization
            [
                'name' => 'Digitalization Employee 1',
                'nrp' => 2011,
                'email' => 'digital_employee_1@example.com',
                'password' => Hash::make('password'),
                'section' => 'Digitalization',
                'department' => 'Digitalization & IT',
                'division' => 'Strategic & IT Development',
                'position' => 'employee',
            ],
            [
                'name' => 'Digitalization Employee 2',
                'nrp' => 2012,
                'email' => 'digital_employee_2@example.com',
                'password' => Hash::make('password'),
                'section' => 'Digitalization',
                'department' => 'Digitalization & IT',
                'division' => 'Strategic & IT Development',
                'position' => 'employee',
            ],
            [
                'name' => 'Digitalization Section Head',
                'nrp' => 2013,
                'email' => 'digital_section_head@example.com',
                'password' => Hash::make('password'),
                'section' => 'Digitalization',
                'department' => 'Digitalization & IT',
                'division' => 'Strategic & IT Development',
                'position' => 'section_head',
            ],

            // Department Head
            [
                'name' => 'Digitalization & IT Dept Head',
                'nrp' => 2090,
                'email' => 'it_dept_head@example.com',
                'password' => Hash::make('password'),
                'section' => null,
                'department' => 'Digitalization & IT',
                'division' => 'Strategic & IT Development',
                'position' => 'department_head',
            ],

            // Department: Safety, Compliance & Strategic
            // Section: Safety
            [
                'name' => 'Safety Employee 1',
                'nrp' => 2101,
                'email' => 'safety_employee_1@example.com',
                'password' => Hash::make('password'),
                'section' => 'Safety',
                'department' => 'Safety, Compliance & Strategic',
                'division' => 'Strategic & IT Development',
                'position' => 'employee',
            ],
            [
                'name' => 'Safety Employee 2',
                'nrp' => 2102,
                'email' => 'safety_employee_2@example.com',
                'password' => Hash::make('password'),
                'section' => 'Safety',
                'department' => 'Safety, Compliance & Strategic',
                'division' => 'Strategic & IT Development',
                'position' => 'employee',
            ],
            [
                'name' => 'Safety Section Head',
                'nrp' => 2103,
                'email' => 'safety_section_head@example.com',
                'password' => Hash::make('password'),
                'section' => 'Safety',
                'department' => 'Safety, Compliance & Strategic',
                'division' => 'Strategic & IT Development',
                'position' => 'section_head',
            ],

            // Section: Compliance
            [
                'name' => 'Compliance Employee 1',
                'nrp' => 2111,
                'email' => 'compliance_employee_1@example.com',
                'password' => Hash::make('password'),
                'section' => 'Compliance',
                'department' => 'Safety, Compliance & Strategic',
                'division' => 'Strategic & IT Development',
                'position' => 'employee',
            ],
            [
                'name' => 'Compliance Employee 2',
                'nrp' => 2112,
                'email' => 'compliance_employee_2@example.com',
                'password' => Hash::make('password'),
                'section' => 'Compliance',
                'department' => 'Safety, Compliance & Strategic',
                'division' => 'Strategic & IT Development',
                'position' => 'employee',
            ],
            [
                'name' => 'Compliance Section Head',
                'nrp' => 2113,
                'email' => 'compliance_section_head@example.com',
                'password' => Hash::make('password'),
                'section' => 'Compliance',
                'department' => 'Safety, Compliance & Strategic',
                'division' => 'Strategic & IT Development',
                'position' => 'section_head',
            ],

            // Section: Strategic
            [
                'name' => 'Strategic Employee 1',
                'nrp' => 2121,
                'email' => 'strategic_employee_1@example.com',
                'password' => Hash::make('password'),
                'section' => 'Strategic',
                'department' => 'Safety, Compliance & Strategic',
                'division' => 'Strategic & IT Development',
                'position' => 'employee',
            ],
            [
                'name' => 'Strategic Employee 2',
                'nrp' => 2122,
                'email' => 'strategic_employee_2@example.com',
                'password' => Hash::make('password'),
                'section' => 'Strategic',
                'department' => 'Safety, Compliance & Strategic',
                'division' => 'Strategic & IT Development',
                'position' => 'employee',
            ],
            [
                'name' => 'Strategic Section Head',
                'nrp' => 2123,
                'email' => 'strategic_section_head@example.com',
                'password' => Hash::make('password'),
                'section' => 'Strategic',
                'department' => 'Safety, Compliance & Strategic',
                'division' => 'Strategic & IT Development',
                'position' => 'section_head',
            ],

            // Department Head
            [
                'name' => 'Safety Compliance Strategic Dept Head',
                'nrp' => 2190,
                'email' => 'safety_dept_head@example.com',
                'password' => Hash::make('password'),
                'section' => null,
                'department' => 'Safety, Compliance & Strategic',
                'division' => 'Strategic & IT Development',
                'position' => 'department_head',
            ],

            // Division Head
            [
                'name' => 'Strategic & IT Division Head',
                'nrp' => 2900,
                'email' => 'strategic_div_head@example.com',
                'password' => Hash::make('password'),
                'section' => null,
                'department' => null,
                'division' => 'Strategic & IT Development',
                'position' => 'division_head',
            ],

            // ========== Production Division ==========
            // Department: Disassembly, Washing & Identification
            // Section: Disassembly
            [
                'name' => 'Disassembly Employee 1',
                'nrp' => 3001,
                'email' => 'disassembly_employee_1@example.com',
                'password' => Hash::make('password'),
                'section' => 'Disassembly',
                'department' => 'Disassembly, Washing & Identification',
                'division' => 'Production',
                'position' => 'employee',
            ],
            [
                'name' => 'Disassembly Employee 2',
                'nrp' => 3002,
                'email' => 'disassembly_employee_2@example.com',
                'password' => Hash::make('password'),
                'section' => 'Disassembly',
                'department' => 'Disassembly, Washing & Identification',
                'division' => 'Production',
                'position' => 'employee',
            ],
            [
                'name' => 'Disassembly Supervisor',
                'nrp' => 3003,
                'email' => 'disassembly_supervisor@example.com',
                'password' => Hash::make('password'),
                'section' => 'Disassembly',
                'department' => 'Disassembly, Washing & Identification',
                'division' => 'Production',
                'position' => 'supervisor',
            ],
            [
                'name' => 'Disassembly Section Head',
                'nrp' => 3004,
                'email' => 'disassembly_section_head@example.com',
                'password' => Hash::make('password'),
                'section' => 'Disassembly',
                'department' => 'Disassembly, Washing & Identification',
                'division' => 'Production',
                'position' => 'section_head',
            ],

            // Section: Washing
            [
                'name' => 'Washing Employee 1',
                'nrp' => 3011,
                'email' => 'washing_employee_1@example.com',
                'password' => Hash::make('password'),
                'section' => 'Washing',
                'department' => 'Disassembly, Washing & Identification',
                'division' => 'Production',
                'position' => 'employee',
            ],
            [
                'name' => 'Washing Employee 2',
                'nrp' => 3012,
                'email' => 'washing_employee_2@example.com',
                'password' => Hash::make('password'),
                'section' => 'Washing',
                'department' => 'Disassembly, Washing & Identification',
                'division' => 'Production',
                'position' => 'employee',
            ],
            [
                'name' => 'Washing Supervisor',
                'nrp' => 3013,
                'email' => 'washing_supervisor@example.com',
                'password' => Hash::make('password'),
                'section' => 'Washing',
                'department' => 'Disassembly, Washing & Identification',
                'division' => 'Production',
                'position' => 'supervisor',
            ],
            [
                'name' => 'Washing Section Head',
                'nrp' => 3014,
                'email' => 'washing_section_head@example.com',
                'password' => Hash::make('password'),
                'section' => 'Washing',
                'department' => 'Disassembly, Washing & Identification',
                'division' => 'Production',
                'position' => 'section_head',
            ],

            // Section: Identification
            [
                'name' => 'Identification Employee 1',
                'nrp' => 3021,
                'email' => 'identification_employee_1@example.com',
                'password' => Hash::make('password'),
                'section' => 'Identification',
                'department' => 'Disassembly, Washing & Identification',
                'division' => 'Production',
                'position' => 'employee',
            ],
            [
                'name' => 'Identification Employee 2',
                'nrp' => 3022,
                'email' => 'identification_employee_2@example.com',
                'password' => Hash::make('password'),
                'section' => 'Identification',
                'department' => 'Disassembly, Washing & Identification',
                'division' => 'Production',
                'position' => 'employee',
            ],
            [
                'name' => 'Identification Supervisor',
                'nrp' => 3023,
                'email' => 'identification_supervisor@example.com',
                'password' => Hash::make('password'),
                'section' => 'Identification',
                'department' => 'Disassembly, Washing & Identification',
                'division' => 'Production',
                'position' => 'supervisor',
            ],
            [
                'name' => 'Identification Section Head',
                'nrp' => 3024,
                'email' => 'identification_section_head@example.com',
                'password' => Hash::make('password'),
                'section' => 'Identification',
                'department' => 'Disassembly, Washing & Identification',
                'division' => 'Production',
                'position' => 'section_head',
            ],

            // Department Head
            [
                'name' => 'Disassembly Washing Dept Head',
                'nrp' => 3090,
                'email' => 'disassembly_dept_head@example.com',
                'password' => Hash::make('password'),
                'section' => null,
                'department' => 'Disassembly, Washing & Identification',
                'division' => 'Production',
                'position' => 'department_head',
            ],

            // Department: Engine Assembly & Cylinder
            // Section: Engine Assembly
            [
                'name' => 'Engine Assembly Employee 1',
                'nrp' => 3101,
                'email' => 'engine_employee_1@example.com',
                'password' => Hash::make('password'),
                'section' => 'Engine Assembly',
                'department' => 'Engine Assembly & Cylinder',
                'division' => 'Production',
                'position' => 'employee',
            ],
            [
                'name' => 'Engine Assembly Employee 2',
                'nrp' => 3102,
                'email' => 'engine_employee_2@example.com',
                'password' => Hash::make('password'),
                'section' => 'Engine Assembly',
                'department' => 'Engine Assembly & Cylinder',
                'division' => 'Production',
                'position' => 'employee',
            ],
            [
                'name' => 'Engine Assembly Supervisor',
                'nrp' => 3103,
                'email' => 'engine_supervisor@example.com',
                'password' => Hash::make('password'),
                'section' => 'Engine Assembly',
                'department' => 'Engine Assembly & Cylinder',
                'division' => 'Production',
                'position' => 'supervisor',
            ],
            [
                'name' => 'Engine Assembly Section Head',
                'nrp' => 3104,
                'email' => 'engine_section_head@example.com',
                'password' => Hash::make('password'),
                'section' => 'Engine Assembly',
                'department' => 'Engine Assembly & Cylinder',
                'division' => 'Production',
                'position' => 'section_head',
            ],

            // Section: Cylinder
            [
                'name' => 'Cylinder Employee 1',
                'nrp' => 3111,
                'email' => 'cylinder_employee_1@example.com',
                'password' => Hash::make('password'),
                'section' => 'Cylinder',
                'department' => 'Engine Assembly & Cylinder',
                'division' => 'Production',
                'position' => 'employee',
            ],
            [
                'name' => 'Cylinder Employee 2',
                'nrp' => 3112,
                'email' => 'cylinder_employee_2@example.com',
                'password' => Hash::make('password'),
                'section' => 'Cylinder',
                'department' => 'Engine Assembly & Cylinder',
                'division' => 'Production',
                'position' => 'employee',
            ],
            [
                'name' => 'Cylinder Supervisor',
                'nrp' => 3113,
                'email' => 'cylinder_supervisor@example.com',
                'password' => Hash::make('password'),
                'section' => 'Cylinder',
                'department' => 'Engine Assembly & Cylinder',
                'division' => 'Production',
                'position' => 'supervisor',
            ],
            [
                'name' => 'Cylinder Section Head',
                'nrp' => 3114,
                'email' => 'cylinder_section_head@example.com',
                'password' => Hash::make('password'),
                'section' => 'Cylinder',
                'department' => 'Engine Assembly & Cylinder',
                'division' => 'Production',
                'position' => 'section_head',
            ],

            // Department Head
            [
                'name' => 'Engine & Cylinder Dept Head',
                'nrp' => 3190,
                'email' => 'engine_dept_head@example.com',
                'password' => Hash::make('password'),
                'section' => null,
                'department' => 'Engine Assembly & Cylinder',
                'division' => 'Production',
                'position' => 'department_head',
            ],

            // Department: Power Train Assembly
            // Section: Power Train Assembly
            [
                'name' => 'Power Train Employee 1',
                'nrp' => 3201,
                'email' => 'powertrain_employee_1@example.com',
                'password' => Hash::make('password'),
                'section' => 'Power Train Assembly',
                'department' => 'Power Train Assembly',
                'division' => 'Production',
                'position' => 'employee',
            ],
            [
                'name' => 'Power Train Employee 2',
                'nrp' => 3202,
                'email' => 'powertrain_employee_2@example.com',
                'password' => Hash::make('password'),
                'section' => 'Power Train Assembly',
                'department' => 'Power Train Assembly',
                'division' => 'Production',
                'position' => 'employee',
            ],
            [
                'name' => 'Power Train Supervisor',
                'nrp' => 3203,
                'email' => 'powertrain_supervisor@example.com',
                'password' => Hash::make('password'),
                'section' => 'Power Train Assembly',
                'department' => 'Power Train Assembly',
                'division' => 'Production',
                'position' => 'supervisor',
            ],
            [
                'name' => 'Power Train Section Head',
                'nrp' => 3204,
                'email' => 'powertrain_section_head@example.com',
                'password' => Hash::make('password'),
                'section' => 'Power Train Assembly',
                'department' => 'Power Train Assembly',
                'division' => 'Production',
                'position' => 'section_head',
            ],

            // Department Head
            [
                'name' => 'Power Train Dept Head',
                'nrp' => 3290,
                'email' => 'powertrain_dept_head@example.com',
                'password' => Hash::make('password'),
                'section' => null,
                'department' => 'Power Train Assembly',
                'division' => 'Production',
                'position' => 'department_head',
            ],

            // Division Head
            [
                'name' => 'Production Division Head',
                'nrp' => 3900,
                'email' => 'production_div_head@example.com',
                'password' => Hash::make('password'),
                'section' => null,
                'department' => null,
                'division' => 'Production',
                'position' => 'division_head',
            ],

            // ========== Quality & Machining Division ==========
            // Department: Quality
            // Section: Quality
            [
                'name' => 'Quality Employee 1',
                'nrp' => 4001,
                'email' => 'quality_employee_1@example.com',
                'password' => Hash::make('password'),
                'section' => 'Quality',
                'department' => 'Quality',
                'division' => 'Quality & Machining',
                'position' => 'employee',
            ],
            [
                'name' => 'Quality Employee 2',
                'nrp' => 4002,
                'email' => 'quality_employee_2@example.com',
                'password' => Hash::make('password'),
                'section' => 'Quality',
                'department' => 'Quality',
                'division' => 'Quality & Machining',
                'position' => 'employee',
            ],
            [
                'name' => 'Quality Supervisor',
                'nrp' => 4003,
                'email' => 'quality_supervisor@example.com',
                'password' => Hash::make('password'),
                'section' => 'Quality',
                'department' => 'Quality',
                'division' => 'Quality & Machining',
                'position' => 'supervisor',
            ],
            [
                'name' => 'Quality Section Head',
                'nrp' => 4004,
                'email' => 'quality_section_head@example.com',
                'password' => Hash::make('password'),
                'section' => 'Quality',
                'department' => 'Quality',
                'division' => 'Quality & Machining',
                'position' => 'section_head',
            ],

            // Department Head
            [
                'name' => 'Quality Dept Head',
                'nrp' => 4090,
                'email' => 'quality_dept_head@example.com',
                'password' => Hash::make('password'),
                'section' => null,
                'department' => 'Quality',
                'division' => 'Quality & Machining',
                'position' => 'department_head',
            ],

            // Department: Machining
            // Section: Machining
            [
                'name' => 'Machining Employee 1',
                'nrp' => 4101,
                'email' => 'machining_employee_1@example.com',
                'password' => Hash::make('password'),
                'section' => 'Machining',
                'department' => 'Machining',
                'division' => 'Quality & Machining',
                'position' => 'employee',
            ],
            [
                'name' => 'Machining Employee 2',
                'nrp' => 4102,
                'email' => 'machining_employee_2@example.com',
                'password' => Hash::make('password'),
                'section' => 'Machining',
                'department' => 'Machining',
                'division' => 'Quality & Machining',
                'position' => 'employee',
            ],
            [
                'name' => 'Machining Supervisor',
                'nrp' => 4103,
                'email' => 'machining_supervisor@example.com',
                'password' => Hash::make('password'),
                'section' => 'Machining',
                'department' => 'Machining',
                'division' => 'Quality & Machining',
                'position' => 'supervisor',
            ],
            [
                'name' => 'Machining Section Head',
                'nrp' => 4104,
                'email' => 'machining_section_head@example.com',
                'password' => Hash::make('password'),
                'section' => 'Machining',
                'department' => 'Machining',
                'division' => 'Quality & Machining',
                'position' => 'section_head',
            ],

            // Department Head
            [
                'name' => 'Machining Dept Head',
                'nrp' => 4190,
                'email' => 'machining_dept_head@example.com',
                'password' => Hash::make('password'),
                'section' => null,
                'department' => 'Machining',
                'division' => 'Quality & Machining',
                'position' => 'department_head',
            ],

            // Division Head
            [
                'name' => 'Quality & Machining Division Head',
                'nrp' => 4900,
                'email' => 'quality_div_head@example.com',
                'password' => Hash::make('password'),
                'section' => null,
                'department' => null,
                'division' => 'Quality & Machining',
                'position' => 'division_head',
            ],

            // ========== Engineering & TPM Division ==========
            // Department: Engineering
            // Section: Engineering
            [
                'name' => 'Engineering Employee 1',
                'nrp' => 5001,
                'email' => 'engineering_employee_1@example.com',
                'password' => Hash::make('password'),
                'section' => 'Engineering',
                'department' => 'Engineering',
                'division' => 'Engineering & TPM',
                'position' => 'employee',
            ],
            [
                'name' => 'Engineering Employee 2',
                'nrp' => 5002,
                'email' => 'engineering_employee_2@example.com',
                'password' => Hash::make('password'),
                'section' => 'Engineering',
                'department' => 'Engineering',
                'division' => 'Engineering & TPM',
                'position' => 'employee',
            ],
            [
                'name' => 'Engineering Supervisor',
                'nrp' => 5003,
                'email' => 'engineering_supervisor@example.com',
                'password' => Hash::make('password'),
                'section' => 'Engineering',
                'department' => 'Engineering',
                'division' => 'Engineering & TPM',
                'position' => 'supervisor',
            ],
            [
                'name' => 'Engineering Section Head',
                'nrp' => 5004,
                'email' => 'engineering_section_head@example.com',
                'password' => Hash::make('password'),
                'section' => 'Engineering',
                'department' => 'Engineering',
                'division' => 'Engineering & TPM',
                'position' => 'section_head',
            ],

            // Department Head
            [
                'name' => 'Engineering Dept Head',
                'nrp' => 5090,
                'email' => 'engineering_dept_head@example.com',
                'password' => Hash::make('password'),
                'section' => null,
                'department' => 'Engineering',
                'division' => 'Engineering & TPM',
                'position' => 'department_head',
            ],

            // Department: TPM & Production Facility
            // Section: TPM
            [
                'name' => 'TPM Employee 1',
                'nrp' => 5101,
                'email' => 'tpm_employee_1@example.com',
                'password' => Hash::make('password'),
                'section' => 'TPM',
                'department' => 'TPM & Production Facility',
                'division' => 'Engineering & TPM',
                'position' => 'employee',
            ],
            [
                'name' => 'TPM Employee 2',
                'nrp' => 5102,
                'email' => 'tpm_employee_2@example.com',
                'password' => Hash::make('password'),
                'section' => 'TPM',
                'department' => 'TPM & Production Facility',
                'division' => 'Engineering & TPM',
                'position' => 'employee',
            ],
            [
                'name' => 'TPM Supervisor',
                'nrp' => 5103,
                'email' => 'tpm_supervisor@example.com',
                'password' => Hash::make('password'),
                'section' => 'TPM',
                'department' => 'TPM & Production Facility',
                'division' => 'Engineering & TPM',
                'position' => 'supervisor',
            ],
            [
                'name' => 'TPM Section Head',
                'nrp' => 5104,
                'email' => 'tpm_section_head@example.com',
                'password' => Hash::make('password'),
                'section' => 'TPM',
                'department' => 'TPM & Production Facility',
                'division' => 'Engineering & TPM',
                'position' => 'section_head',
            ],

            // Section: Production Facility
            [
                'name' => 'Production Facility Employee 1',
                'nrp' => 5111,
                'email' => 'facility_employee_1@example.com',
                'password' => Hash::make('password'),
                'section' => 'Production Facility',
                'department' => 'TPM & Production Facility',
                'division' => 'Engineering & TPM',
                'position' => 'employee',
            ],
            [
                'name' => 'Production Facility Employee 2',
                'nrp' => 5112,
                'email' => 'facility_employee_2@example.com',
                'password' => Hash::make('password'),
                'section' => 'Production Facility',
                'department' => 'TPM & Production Facility',
                'division' => 'Engineering & TPM',
                'position' => 'employee',
            ],
            [
                'name' => 'Production Facility Supervisor',
                'nrp' => 5113,
                'email' => 'facility_supervisor@example.com',
                'password' => Hash::make('password'),
                'section' => 'Production Facility',
                'department' => 'TPM & Production Facility',
                'division' => 'Engineering & TPM',
                'position' => 'supervisor',
            ],
            [
                'name' => 'Production Facility Section Head',
                'nrp' => 5114,
                'email' => 'facility_section_head@example.com',
                'password' => Hash::make('password'),
                'section' => 'Production Facility',
                'department' => 'TPM & Production Facility',
                'division' => 'Engineering & TPM',
                'position' => 'section_head',
            ],

            // Department Head
            [
                'name' => 'TPM & Facility Dept Head',
                'nrp' => 5190,
                'email' => 'tpm_dept_head@example.com',
                'password' => Hash::make('password'),
                'section' => null,
                'department' => 'TPM & Production Facility',
                'division' => 'Engineering & TPM',
                'position' => 'department_head',
            ],

            // Division Head
            [
                'name' => 'Engineering & TPM Division Head',
                'nrp' => 5900,
                'email' => 'engineering_div_head@example.com',
                'password' => Hash::make('password'),
                'section' => null,
                'department' => null,
                'division' => 'Engineering & TPM',
                'position' => 'division_head',
            ],

            // ========== Demand & Supply Chain Division ==========
            // Department: PPIC & Marketing
            // Section: PPIC
            [
                'name' => 'PPIC Employee 1',
                'nrp' => 6001,
                'email' => 'ppic_employee_1@example.com',
                'password' => Hash::make('password'),
                'section' => 'PPIC',
                'department' => 'PPIC & Marketing',
                'division' => 'Demand & Supply Chain',
                'position' => 'employee',
            ],
            [
                'name' => 'PPIC Employee 2',
                'nrp' => 6002,
                'email' => 'ppic_employee_2@example.com',
                'password' => Hash::make('password'),
                'section' => 'PPIC',
                'department' => 'PPIC & Marketing',
                'division' => 'Demand & Supply Chain',
                'position' => 'employee',
            ],
            [
                'name' => 'PPIC Section Head',
                'nrp' => 6003,
                'email' => 'ppic_section_head@example.com',
                'password' => Hash::make('password'),
                'section' => 'PPIC',
                'department' => 'PPIC & Marketing',
                'division' => 'Demand & Supply Chain',
                'position' => 'section_head',
            ],

            // Section: Marketing
            [
                'name' => 'Marketing Employee 1',
                'nrp' => 6011,
                'email' => 'marketing_employee_1@example.com',
                'password' => Hash::make('password'),
                'section' => 'Marketing',
                'department' => 'PPIC & Marketing',
                'division' => 'Demand & Supply Chain',
                'position' => 'employee',
            ],
            [
                'name' => 'Marketing Employee 2',
                'nrp' => 6012,
                'email' => 'marketing_employee_2@example.com',
                'password' => Hash::make('password'),
                'section' => 'Marketing',
                'department' => 'PPIC & Marketing',
                'division' => 'Demand & Supply Chain',
                'position' => 'employee',
            ],
            [
                'name' => 'Marketing Section Head',
                'nrp' => 6013,
                'email' => 'marketing_section_head@example.com',
                'password' => Hash::make('password'),
                'section' => 'Marketing',
                'department' => 'PPIC & Marketing',
                'division' => 'Demand & Supply Chain',
                'position' => 'section_head',
            ],

            // Department Head
            [
                'name' => 'PPIC & Marketing Dept Head',
                'nrp' => 6090,
                'email' => 'ppic_dept_head@example.com',
                'password' => Hash::make('password'),
                'section' => null,
                'department' => 'PPIC & Marketing',
                'division' => 'Demand & Supply Chain',
                'position' => 'department_head',
            ],

            // Department: Procurement & Warehouse
            // Section: Procurement
            [
                'name' => 'Procurement Employee 1',
                'nrp' => 6101,
                'email' => 'procurement_employee_1@example.com',
                'password' => Hash::make('password'),
                'section' => 'Procurement',
                'department' => 'Procurement & Warehouse',
                'division' => 'Demand & Supply Chain',
                'position' => 'employee',
            ],
            [
                'name' => 'Procurement Employee 2',
                'nrp' => 6102,
                'email' => 'procurement_employee_2@example.com',
                'password' => Hash::make('password'),
                'section' => 'Procurement',
                'department' => 'Procurement & Warehouse',
                'division' => 'Demand & Supply Chain',
                'position' => 'employee',
            ],
            [
                'name' => 'Procurement Section Head',
                'nrp' => 6103,
                'email' => 'procurement_section_head@example.com',
                'password' => Hash::make('password'),
                'section' => 'Procurement',
                'department' => 'Procurement & Warehouse',
                'division' => 'Demand & Supply Chain',
                'position' => 'section_head',
            ],

            // Section: Warehouse
            [
                'name' => 'Warehouse Employee 1',
                'nrp' => 6111,
                'email' => 'warehouse_employee_1@example.com',
                'password' => Hash::make('password'),
                'section' => 'Warehouse',
                'department' => 'Procurement & Warehouse',
                'division' => 'Demand & Supply Chain',
                'position' => 'employee',
            ],
            [
                'name' => 'Warehouse Employee 2',
                'nrp' => 6112,
                'email' => 'warehouse_employee_2@example.com',
                'password' => Hash::make('password'),
                'section' => 'Warehouse',
                'department' => 'Procurement & Warehouse',
                'division' => 'Demand & Supply Chain',
                'position' => 'employee',
            ],
            [
                'name' => 'Warehouse Section Head',
                'nrp' => 6113,
                'email' => 'warehouse_section_head@example.com',
                'password' => Hash::make('password'),
                'section' => 'Warehouse',
                'department' => 'Procurement & Warehouse',
                'division' => 'Demand & Supply Chain',
                'position' => 'section_head',
            ],

            // Department Head
            [
                'name' => 'Procurement & Warehouse Dept Head',
                'nrp' => 6190,
                'email' => 'procurement_dept_head@example.com',
                'password' => Hash::make('password'),
                'section' => null,
                'department' => 'Procurement & Warehouse',
                'division' => 'Demand & Supply Chain',
                'position' => 'department_head',
            ],

            // Division Head
            [
                'name' => 'Demand & Supply Chain Division Head',
                'nrp' => 6900,
                'email' => 'supply_div_head@example.com',
                'password' => Hash::make('password'),
                'section' => null,
                'department' => null,
                'division' => 'Demand & Supply Chain',
                'position' => 'division_head',
            ],
        ];

        foreach ($users as $userData) {
            User::create($userData);
        }

        // Add roles to specific users (many-to-many relationship via user_roles)
        $lidStaff1 = User::where('nrp', 1001)->first();
        if ($lidStaff1) {
            DB::table('user_roles')->insert([
                ['user_id' => $lidStaff1->id, 'role' => 'multimedia', 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        $lidStaff2 = User::where('nrp', 1002)->first();
        if ($lidStaff2) {
            DB::table('user_roles')->insert([
                ['user_id' => $lidStaff2->id, 'role' => 'admin', 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        $lidStaff3 = User::where('nrp', 1003)->first();
        if ($lidStaff3) {
            DB::table('user_roles')->insert([
                ['user_id' => $lidStaff3->id, 'role' => 'instructor', 'created_at' => now(), 'updated_at' => now()],
            ]);
        }

        $lidStaff4 = User::where('nrp', 1004)->first();
        if ($lidStaff4) {
            DB::table('user_roles')->insert([
                ['user_id' => $lidStaff4->id, 'role' => 'certificator', 'created_at' => now(), 'updated_at' => now()],
            ]);
        }
    }
}
