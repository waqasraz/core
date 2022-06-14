<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

namespace Gibbon\Module\Admissions\Tables;

use Gibbon\View\View;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Contracts\Services\Session;
use Gibbon\Domain\Admissions\AdmissionsApplicationGateway;

/**
 * ApplicationFamilyTable
 *
 * @version v24
 * @since   v24
 */
class ApplicationFamilyTable extends DataTable
{
    protected $view;
    protected $session;
    protected $formUploadGateway;

    public function __construct(Session $session, View $view, AdmissionsApplicationGateway $applicationGateway)
    {
        $this->view = $view;
        $this->session = $session;
        $this->applicationGateway = $applicationGateway;

    }

    public function createTable($gibbonAdmissionsApplicationID, $gibbonFamilyID)
    {
        // Load related documents
        $criteria = $this->applicationGateway->newQueryCriteria()->fromPOST();
        $family = $this->applicationGateway->queryFamilyByApplication($criteria, $this->session->get('gibbonSchoolYearID'), $gibbonAdmissionsApplicationID);

        // Create the table
        $table = DataTable::create('applicationFamily')->withData($family);
        
        $table->modifyRows(function ($values, $row) {
            if ($values['status'] == 'Full') $row->addClass('success');
            if ($values['status'] == 'Expected') $row->addClass('message');
            if ($values['status'] == 'Incomplete') $row->addClass('warning');
            if ($values['status'] == 'Rejected') $row->addClass('error');
            if ($values['status'] == 'Accepted') $row->addClass('success');
            if ($values['status'] == 'Withdrawn') $row->addClass('error');
            return $row;
        });

        if (!empty($gibbonFamilyID)) {
            $table->addHeaderAction('edit', __('Edit Family'))
                ->setURL('/modules/User Admin/family_manage_edit.php')
                ->addParam('gibbonFamilyID', $gibbonFamilyID)
                ->displayLabel();
        }

        $table->addColumn('image_240', __('Photo'))
            ->context('primary')
            ->width('10%')
            ->notSortable()
            ->format(Format::using('userPhoto', ['image_240', 'sm']));

        $table->addColumn('person', __('Person'))
            ->description(__('Status'))
            ->format(function ($values) {
                return Format::bold(Format::name('', $values['surname'], $values['preferredName'], 'Student', true));
            })
            ->formatDetails(function ($values) {
                return Format::small($values['roleCategory'] == 'Student' ? Format::userStatusInfo($values) : $values['status']);
            });

        $table->addColumn('roleCategory', __('Role'))
            ->description(__('Relationship'))
            ->formatDetails(function ($values) {
                return Format::small($values['relationship']);
            });

        $table->addColumn('email', __('Email'));

        $table->addColumn('details', __('Details'))
            ->format(function ($values) {
                if ($values['roleCategory'] == 'Parent' && ($values['status'] == 'Full' || $values['status'] == 'Expected')) {
                    return __('Existing Parent');
                } elseif ($values['roleCategory'] == 'Parent' && $values['status'] == 'Pending') {
                    return __('New Parent');
                } 
                return $values['yearGroup'];
            });

        return $table;
    }
}