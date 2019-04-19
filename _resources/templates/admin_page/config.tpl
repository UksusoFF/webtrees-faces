<div id="pnwim-config">
    <ul class="nav nav-pills navbar-right" role="tablist">
        <li role="presentation" class="dropdown">
            <a href="#"
               class="dropdown-toggle"
               data-toggle="dropdown">
                Missed notes <span class="caret"></span>
            </a>
            <ul class="dropdown-menu">
                <li>
                    <a href="#"
                       title="Remove all notes without media"
                       data-action="missed-delete"
                       data-url="[@missedDeleteUrl]">
                        <span class="text-danger">
                            <i class="fa fa-fw fa-trash"></i>
                            Remove
                        </span>
                    </a>
                </li>
                <li>
                    <a href="#"
                       title="Try to find missed notes by filename"
                       data-action="missed-repair"
                       data-url="[@missedRepairUrl]">
                        <span class="text-success">
                            <i class="fa fa-fw fa-magic"></i>
                            Find
                        </span>
                    </a>
                </li>
            </ul>
        </li>
        <li role="presentation" class="dropdown">
            <a href="#"
               class="dropdown-toggle"
               data-toggle="dropdown">
                Settings <span class="caret"></span>
            </a>
            <ul class="dropdown-menu">
                <li>
                    <a href="#"
                       title="Read and show XMP data (such as Goggle Picasa face tags) from photos"
                       data-action="exif-toggle"
                       data-url="[@exifToggleUrl]">
                        <i class="fa fa-fw [@exifState]"></i>
                        Read XMP data
                    </a>
                </li>
            </ul>
        </li>
    </ul>

    <h3>[@pageTitle]</h3>
    <table id="pnwim-media-table" class="table" data-url="[@dataActionUrl]">
        <thead>
        <tr>
            <th>Photo</th>
            <th>Notes</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>