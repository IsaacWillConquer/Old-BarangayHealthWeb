<?php
require_once __DIR__ . "/../../../core/auth.php";
mustBeResident();
$activePage = 'profile';
$isDefault = isDefaultPassword();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <base href="<?php echo BASE_URL; ?>">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="app/Views/CSS/generalize.css">
    <title>My Profile</title>
</head>
<body>
<div class="app-shell">
    <?php include __DIR__ . '/../Partials/sidebarResident.php'; ?>

    <div class="main-content">
        <div class="page-header"><h2>My Profile</h2></div>

        <div class="grid-2">
            <div class="card">
                <h3>Profile Photo</h3>
                <img id="profileImg" src="" style="width:140px;height:140px;border-radius:50%;object-fit:cover;background:#eee;display:block;margin-bottom:14px;">
                <input type="file" id="imageInput" accept="image/jpeg,image/png">
                <p style="color:var(--text-muted);font-size:0.78rem;">JPG or PNG only, up to 3MB.</p>
                <button class="btn small" id="uploadBtn">Upload Photo</button>
                <div id="imageMsg" class="success-text"></div>
            </div>

            <div class="card">
                <h3>Basic Information</h3>
                <label>Resident Number</label>
                <input type="text" id="pNumber" disabled>
                <label>First Name</label>
                <input type="text" id="pFirstName" disabled>
                <label>Middle Name</label>
                <input type="text" id="pMiddleName">
                <label>Last Name</label>
                <input type="text" id="pLastName" disabled>
                <label>Extension Name (Jr., Sr., III...)</label>
                <input type="text" id="pExtensionName">
                <label>Civil Status</label>
                <select id="pCivilStatus">
                    <option value="">Select...</option>
                    <option value="Single">Single</option>
                    <option value="Married">Married</option>
                    <option value="Widowed">Widowed</option>
                    <option value="Separated">Separated</option>
                    <option value="Divorced">Divorced</option>
                </select>
                <label>Gender</label>
                <select id="pGender">
                    <option value="">Select...</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                </select>
                <label>Birthday</label>
                <input type="date" id="pBirthday">
                <label>Age</label>
                <input type="text" id="pAge" disabled>
                <label>Bio (optional)</label>
                <textarea id="pBio" rows="2"></textarea>
            </div>
        </div>

        <div class="grid-2">
            <div class="card">
                <h3>Contact Information</h3>
                <label>Address</label>
                <input type="text" id="pAddress">
                <label>2nd Address (optional)</label>
                <input type="text" id="pAddress2">
                <label>Contact Number</label>
                <input type="text" id="pContact" inputmode="numeric" maxlength="11" placeholder="11-digit number">
                <label>Home Phone (optional)</label>
                <input type="text" id="pHomePhone" inputmode="numeric" maxlength="11">
                <label>Email (optional)</label>
                <input type="email" id="pEmail">
                <h4 style="margin-top:16px;">Emergency Contact</h4>
                <label>Name</label>
                <input type="text" id="pEmergencyName">
                <label>Relationship</label>
                <input type="text" id="pEmergencyRelationship">
                <label>Number</label>
                <input type="text" id="pEmergencyNumber" inputmode="numeric" maxlength="11">
            </div>

            <div class="card">
                <h3>Occupation (optional)</h3>
                <label>Occupation</label>
                <input type="text" id="pOccupation">
                <label>Occupation Location</label>
                <input type="text" id="pOccupationLocation">

                <div id="spouseSection" style="display:none;">
                    <h3 style="margin-top:16px;">Spouse Information</h3>
                    <label>Spouse Name</label>
                    <input type="text" id="sName">
                    <label>Spouse Birthdate</label>
                    <input type="date" id="sBirthdate">
                    <label>Spouse Civil Status</label>
                    <select id="sCivilStatus">
                        <option value="">Select...</option>
                        <option value="Single">Single</option>
                        <option value="Married">Married</option>
                        <option value="Widowed">Widowed</option>
                        <option value="Separated">Separated</option>
                        <option value="Divorced">Divorced</option>
                    </select>
                    <label>Spouse Status</label>
                    <select id="sStatus">
                        <option value="Alive">Alive</option>
                        <option value="Deceased">Deceased</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="card">
            <h3>Children</h3>
            <div id="childrenRows"></div>
            <button type="button" class="btn small secondary" id="addChildRowBtn">+ Add Child</button>
        </div>

        <div class="card">
            <button class="btn" id="saveProfileBtn">Save Changes</button>
            <div id="profileMsg" class="success-text"></div>
            <div id="profileErr" class="error-text"></div>
        </div>

        <div class="card" style="max-width:520px;">
            <h3>Password</h3>
            <?php if ($isDefault): ?>
                <p style="color:var(--gold);font-size:0.88rem;background:var(--gold-soft);padding:8px 12px;border-radius:8px;">You're still using your default password (your Resident Number). Consider setting a new one below.</p>
            <?php else: ?>
                <p style="color:var(--text-muted);font-size:0.88rem;">Update your password any time below.</p>
            <?php endif; ?>
            <label>New Password</label>
            <input type="password" id="newpass" placeholder="At least 7 characters">
            <label>Confirm Password</label>
            <input type="password" id="confirmpass" placeholder="Re-enter password">
            <button class="btn" id="changePassBtn" style="margin-top:14px;">Update Password</button>
            <div id="passMsg" class="success-text"></div>
            <div id="passErr" class="error-text"></div>
        </div>
    </div>
</div>

<script>
var BASE_URL_JS = '<?php echo BASE_URL; ?>';
var CTRL = 'app/Controllers/profileController.php';
var currentChildren = [];

function toggleSpouseSection() {
    $('#spouseSection').toggle($('#pCivilStatus').val() === 'Married');
}

function renderChildRow(child) {
    var idx = currentChildren.length;
    currentChildren.push(child || { ChildID: null, LastName: '', FirstName: '', Birthdate: '', Gender: '', Status: 'Living' });
    var c = currentChildren[idx];
    var $row = $(
        '<div class="child-row" data-idx="' + idx + '" style="border:1px solid var(--border,#ddd);padding:10px;margin-bottom:8px;border-radius:6px;">' +
        '<div style="display:flex;gap:8px;flex-wrap:wrap;">' +
        '<input type="text" class="c-lastname" placeholder="Last name" style="flex:1;min-width:120px;" value="' + $('<div>').text(c.LastName || '').html() + '">' +
        '<input type="text" class="c-firstname" placeholder="First name" style="flex:1;min-width:120px;" value="' + $('<div>').text(c.FirstName || '').html() + '">' +
        '<input type="date" class="c-birthdate" style="flex:1;min-width:140px;" value="' + (c.Birthdate || '') + '">' +
        '<select class="c-gender" style="flex:1;min-width:100px;">' +
        '<option value="">Gender</option><option value="Male"' + (c.Gender==='Male'?' selected':'') + '>Male</option>' +
        '<option value="Female"' + (c.Gender==='Female'?' selected':'') + '>Female</option>' +
        '<option value="Other"' + (c.Gender==='Other'?' selected':'') + '>Other</option></select>' +
        '<select class="c-status" style="flex:1;min-width:100px;">' +
        '<option value="Living"' + (c.Status==='Living'?' selected':'') + '>Living</option>' +
        '<option value="Deceased"' + (c.Status==='Deceased'?' selected':'') + '>Deceased</option></select>' +
        '<button type="button" class="btn small danger remove-child-btn">Remove</button>' +
        '</div></div>'
    );
    $('#childrenRows').append($row);
}

function loadProfile() {
    $.post(CTRL, { action: 'getProfile' }, null, 'json')
    .done(function (res) {
        if (!res.success) return;
        var p = res.profile;
        $('#pNumber').val(p.ResidentNumber);
        $('#pFirstName').val(p.FirstName);
        $('#pMiddleName').val(p.MiddleName);
        $('#pLastName').val(p.LastName);
        $('#pExtensionName').val(p.ExtensionName);
        $('#pCivilStatus').val(p.CivilStatus);
        $('#pGender').val(p.Gender);
        $('#pBirthday').val(p.Birthday);
        $('#pAge').val(p.age !== null ? p.age : '');
        $('#pBio').val(p.Bio);
        $('#pAddress').val(p.Address);
        $('#pAddress2').val(p.Address2);
        $('#pContact').val(p.ContactNumber);
        $('#pHomePhone').val(p.HomePhone);
        $('#pEmail').val(p.Email);
        $('#pEmergencyName').val(p.EmergencyContactName);
        $('#pEmergencyRelationship').val(p.EmergencyContactRelationship);
        $('#pEmergencyNumber').val(p.EmergencyContactNumber);
        $('#pOccupation').val(p.Occupation);
        $('#pOccupationLocation').val(p.OccupationLocation);
        $('#profileImg').attr('src', p.ProfileImage ? (BASE_URL_JS + p.ProfileImage) : 'https://via.placeholder.com/140?text=No+Photo');

        if (p.spouse) {
            $('#sName').val(p.spouse.SpouseName);
            $('#sBirthdate').val(p.spouse.SpouseBirthdate);
            $('#sCivilStatus').val(p.spouse.SpouseCivilStatus);
            $('#sStatus').val(p.spouse.SpouseStatus);
        }
        toggleSpouseSection();

        currentChildren = [];
        $('#childrenRows').empty();
        (p.children || []).forEach(function (c) { renderChildRow(c); });
    });
}

$(function () {
    loadProfile();

    $('#pCivilStatus').on('change', toggleSpouseSection);
    $('#pBirthday').on('change', function () {
        if (!this.value) return;
        var bd = new Date(this.value), today = new Date();
        var age = today.getFullYear() - bd.getFullYear();
        if (today.getMonth() < bd.getMonth() || (today.getMonth() === bd.getMonth() && today.getDate() < bd.getDate())) age--;
        $('#pAge').val(age);
    });
    $('#pContact, #pHomePhone, #pEmergencyNumber').on('input', function () {
        this.value = this.value.replace(/\D/g, '').slice(0, 11);
    });

    $('#addChildRowBtn').on('click', function () { renderChildRow(null); });

    $(document).on('click', '.remove-child-btn', function () {
        var $row = $(this).closest('.child-row');
        var idx = parseInt($row.data('idx'));
        var child = currentChildren[idx];
        if (child.ChildID) {
            if (!confirm('Remove this child record? This cannot be undone.')) return;
            $.post(CTRL, { action: 'deleteChild', childId: child.ChildID }, null, 'json')
            .done(function () { $row.remove(); });
        } else {
            $row.remove();
        }
    });

    $('#saveProfileBtn').on('click', function () {
        $('#profileMsg, #profileErr').text('');

        var payload = {
            action: 'updateProfile',
            middleName: $('#pMiddleName').val().trim(),
            extensionName: $('#pExtensionName').val().trim(),
            civilStatus: $('#pCivilStatus').val(),
            gender: $('#pGender').val(),
            birthday: $('#pBirthday').val(),
            bio: $('#pBio').val().trim(),
            address: $('#pAddress').val().trim(),
            address2: $('#pAddress2').val().trim(),
            contactNumber: $('#pContact').val().trim(),
            homePhone: $('#pHomePhone').val().trim(),
            email: $('#pEmail').val().trim(),
            emergencyContactName: $('#pEmergencyName').val().trim(),
            emergencyContactRelationship: $('#pEmergencyRelationship').val().trim(),
            emergencyContactNumber: $('#pEmergencyNumber').val().trim(),
            occupation: $('#pOccupation').val().trim(),
            occupationLocation: $('#pOccupationLocation').val().trim()
        };
        if (payload.civilStatus === 'Married') {
            payload.spouseName = $('#sName').val().trim();
            payload.spouseBirthdate = $('#sBirthdate').val();
            payload.spouseCivilStatus = $('#sCivilStatus').val();
            payload.spouseStatus = $('#sStatus').val();
        }

        $.post(CTRL, payload, null, 'json')
        .done(function (res) {
            if (!res.success) { $('#profileErr').text(res.message); return; }

            var childPromises = [];
            $('.child-row').each(function () {
                var idx = parseInt($(this).data('idx'));
                var child = currentChildren[idx];
                var childPayload = {
                    lastName: $(this).find('.c-lastname').val().trim(),
                    firstName: $(this).find('.c-firstname').val().trim(),
                    birthdate: $(this).find('.c-birthdate').val(),
                    gender: $(this).find('.c-gender').val(),
                    status: $(this).find('.c-status').val()
                };
                childPayload.action = child.ChildID ? 'updateChild' : 'addChild';
                if (child.ChildID) childPayload.childId = child.ChildID;
                childPromises.push($.post(CTRL, childPayload, null, 'json'));
            });

            $.when.apply($, childPromises).always(function () {
                $('#profileMsg').text('Profile updated successfully.');
                loadProfile();
            });
        });
    });

    $('#uploadBtn').on('click', function () {
        var fileInput = document.getElementById('imageInput');
        if (!fileInput.files.length) return;
        var formData = new FormData();
        formData.append('action', 'uploadProfileImage');
        formData.append('profileImage', fileInput.files[0]);

        $.ajax({
            url: CTRL, type: 'POST', data: formData,
            processData: false, contentType: false, dataType: 'json'
        }).done(function (res) {
            if (res.success) { $('#profileImg').attr('src', res.imagePath); $('#imageMsg').text('Photo updated.'); }
            else { $('#imageMsg').css('color', '#b3462c').text(res.message); }
        });
    });

    $('#changePassBtn').on('click', function () {
        var np = $('#newpass').val();
        var cp = $('#confirmpass').val();
        $('#passMsg, #passErr').text('');

        if (np.length < 7) { $('#passErr').text('Password must be at least 7 characters.'); return; }
        if (np !== cp) { $('#passErr').text('Passwords do not match.'); return; }

        $.post('app/Controllers/authController.php', { action: 'changepass', newpass: np }, null, 'json')
        .done(function (res) {
            if (res.success) {
                $('#passMsg').text('Password updated successfully.');
                $('#newpass, #confirmpass').val('');
            } else {
                $('#passErr').text(res.message || 'Something went wrong.');
            }
        });
    });
});
</script>
</body>
</html>
