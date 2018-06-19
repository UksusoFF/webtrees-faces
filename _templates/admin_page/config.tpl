<div id="pnwim-config">
    <div class="pull-right">
    <span class="btn btn-danger"
          title="Remove all notes without media"
          data-action="missed-delete"
          data-url="[@missedDeleteUrl]">
        <i class="fa fa-trash" aria-hidden="true"></i>
        Remove missed notes
    </span>
        <span class="btn btn-success"
              title="Try to find missed notes by filename"
              data-action="missed-repair"
              data-url="[@missedRepairUrl]">
        <i class="fa fa-magic" aria-hidden="true"></i>
        Find missed notes
    </span>
    </div>
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