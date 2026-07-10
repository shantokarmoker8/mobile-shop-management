// ================================================
// Sidebar Toggle (Mobile)
// ================================================
document.addEventListener("DOMContentLoaded", function () {
  const toggleBtn = document.getElementById("sidebarToggle");
  const sidebar = document.getElementById("sidebar");

  if (toggleBtn && sidebar) {
    toggleBtn.addEventListener("click", function () {
      sidebar.classList.toggle("show");
    });

    document.addEventListener("click", function (e) {
      if (window.innerWidth <= 991) {
        if (!sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
          sidebar.classList.remove("show");
        }
      }
    });
  }

  if (typeof gsap !== "undefined") {
    gsap.from(".stat-card", {
      opacity: 0,
      y: 15,
      duration: 0.5,
      stagger: 0.05,
      ease: "power2.out",
    });
    gsap.from(".card-panel", {
      opacity: 0,
      y: 15,
      duration: 0.5,
      delay: 0.1,
      ease: "power2.out",
    });
  }
});

// ================================================
// Show / Hide Password Toggle (used across the app)
// ================================================
function togglePassword(inputId, btn) {
  const input = document.getElementById(inputId);
  const icon = btn.querySelector("i");
  if (!input) return;
  if (input.type === "password") {
    input.type = "text";
    icon.classList.remove("bi-eye");
    icon.classList.add("bi-eye-slash");
  } else {
    input.type = "password";
    icon.classList.remove("bi-eye-slash");
    icon.classList.add("bi-eye");
  }
}

// ================================================
// Logout Confirmation (SweetAlert2)
// ================================================
function confirmLogout(url) {
  Swal.fire({
    title: "Logout?",
    text: "Are you sure you want to logout?",
    icon: "question",
    showCancelButton: true,
    confirmButtonColor: "#2F5BE0",
    cancelButtonColor: "#6b7280",
    confirmButtonText: "Yes, logout",
  }).then((result) => {
    if (result.isConfirmed) {
      window.location.href = url;
    }
  });
}

// ================================================
// Confirm Delete (link-based)
// ================================================
function confirmDelete(url) {
  Swal.fire({
    title: "Are you sure?",
    text: "This action cannot be undone.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#e14343",
    cancelButtonColor: "#6b7280",
    confirmButtonText: "Yes, delete it",
  }).then((result) => {
    if (result.isConfirmed) {
      window.location.href = url;
    }
  });
}

// ================================================
// Universal Delete Form Handler (POST-based delete forms with class "delete-form")
// ================================================
document.addEventListener("submit", function (e) {
  if (e.target.classList && e.target.classList.contains("delete-form")) {
    e.preventDefault();
    Swal.fire({
      title: "Are you sure?",
      text: "This action cannot be undone.",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#e14343",
      cancelButtonColor: "#6b7280",
      confirmButtonText: "Yes, delete it",
    }).then((result) => {
      if (result.isConfirmed) {
        e.target.submit();
      }
    });
  }
});

// ================================================
// Toast Alert Helper
// ================================================
function showToast(icon, message) {
  Swal.fire({
    icon: icon,
    title: message,
    toast: true,
    position: "top-end",
    timer: 2200,
    showConfirmButton: false,
  });
}

function formatMoney(num) {
  return parseFloat(num).toLocaleString("en-US", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
}

function printInvoice() {
  window.print();
}

function calcRowTotal(row) {
  const qty = parseFloat(row.querySelector(".qty-input")?.value) || 0;
  const price = parseFloat(row.querySelector(".price-input")?.value) || 0;
  const total = qty * price;
  const totalDisplay = row.querySelector(".total-display");
  if (totalDisplay) totalDisplay.value = total.toFixed(2);
  return total;
}
