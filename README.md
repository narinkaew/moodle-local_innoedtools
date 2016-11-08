Inno Ed Tools - Moodle local plugin
========================

Adds new items to the navigation block with 2 sub menus. 

Inno Smart Report - allowing you to display the specific report on 3 different types. The tag data is processed from student blog entry that associated to course.

Inno Smart Portfolio - allowing you to display user blog entry that associated to course. it allows user to export file in PDF format. After printing the url link will generated to the QR code. its allow anyone to use a smart phone or other device to scan the QR cde and gain direct access to a specified link.

# Installation

Extract the plugin to /local/innoedtools

Run the Moodle upgrade.

# Prerequisite Setup (admin/course creator)

## Standard tags setting

In 'Site pages > Tags', click on 'Manage tags'

Add/Edit standard tags to any collections you want

## Courses settings

In 'Administration > Courses > Manage courses and categories', input 'Course ID number' value as an abbreviation course name.

The plugin is focus at 'Course ID number' to specify courses to report.

# Usage

## Inno Smart Report

If the current user has the 'local/innoedtools:viewalltagreport' capability then the report setting in the page will appear

There are 3 different types of report

At any time you can choose 'Report settings' and it will displays tag report for that type.

Others user will display report of 'Standard tags' statistics of their own that using in the blogs.

## Inno Smart Portfolio

If the current user has the 'local/innoedtools:canviewinnovationpdf' capability then list of all students page will appear

You can select to view detail of any student.

Others user can see the blog listing that associated to course of their own.