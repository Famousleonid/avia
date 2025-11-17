<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Access denied</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>

<body class="bg-light">

<div class="modal fade show"
     id="errorModal"
     tabindex="-1"
     style="display:block; background: rgba(0,0,0,0.5);"
     aria-modal="true" role="dialog">

  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">

      <div class="modal-header bg-warning-subtle">
        <h5 class="modal-title">Access denied (403)</h5>
      </div>

      <div class="modal-body">
        <p class="mb-0">You do not have permission to perform this action.</p>
      </div>

      <div class="modal-footer">
        <a href="{{ url()->previous() }}" class="btn btn-primary">Return</a>
      </div>

    </div>
  </div>
</div>

</body>
</html>
