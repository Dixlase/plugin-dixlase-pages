{{--
This file is part of Dixlase Pages.

Copyright (C) 2026 exc-D inc.
Website: https://exc-d.com

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
--}}

@extends('layouts.admin')

@section('content')
    <x-revision.list
        :revisions="$revisions"
        :typeLabels="$typeLabels"
        :retention="$retention"
        :protectedCount="$protectedCount"
        :backRoute="route('dixlase-pages::admin.pages.edit', $page)"
        :parentParams="['page' => $page->id]"
        showRouteName="dixlase-pages::admin.pages.revisions.show"
        restoreRouteName="dixlase-pages::admin.pages.revisions.restore"
        protectRouteName="dixlase-pages::admin.pages.revisions.protect"
        translationPrefix="dixlase-pages::admin/pages/revisions"
    />
@endsection
