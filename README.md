Inno Ed Tools - Moodle local plugin
========================

Adds new items to the navigation block under 'Site pages > Inno Ed Tools' with 2 sub menus. 

Inno Smart Report - allowing you to display the specific report on 3 different types. The tag data is processed from student blog entry that associated to course.

Inno Smart Portfolio - allowing you to display user blog entry that associated to course. it allows user to export file in PDF format. After printing the url link will generated to the QR code. its allow anyone to use a smart phone or other device to scan the QR code and gain direct access to a specified link.

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

The menu would provide under 'Site pages', name is 'Inno Ed Tools' with 2 sub menus. The plugin would collect statistics of standard tag that related to courses of blog entry. Teachers used to view reports of each course on 3 different types that can achieve the objectives under the tags.

Also provide summary blog entries for each student that can be exported as a pdf file to a portfolio.

## Inno Smart Report

Allowing you to display the specific report on 3 different types. The tag data is processed from student blog entry that associated to course.

If the current user has the 'local/innoedtools:viewalltagreport' capability then the report setting in the page will appear

There are 3 different types of report

### Report by overall

The report displays “Standard tags” statistics of the all students that using in the blogs. Including overall statistics for all courses in percentage per student.

### Report by tags

The report displays overall “Standard tags” statistics for all students group by tags.

### Report by students

The report displays overall “Standard tags” statistics for all tags group by students.

At any time you can choose 'Report settings' and it will displays tag report for that type.

Others user will display report of 'Standard tags' statistics of their own that using in the blogs.

## Inno Smart Portfolio

Allowing you to display user blog entry that associated to course. it allows user to export file in PDF format. After printing the url link will generated to the QR code. its allow anyone to use a smart phone or other device to scan the QR cde and gain direct access to a specified link.

If the current user has the 'local/innoedtools:canviewinnovationpdf' capability then list of all students page will appear

You can select to view detail of any student.

Others user can see the blog listing that associated to course of their own.

# Document

User manual is provided in 'Document' folder.