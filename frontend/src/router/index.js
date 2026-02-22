import { createRouter, createWebHashHistory } from "vue-router";

import Home from "../views/Home.vue";
import LoginView from "../views/LoginView.vue";
import RegisterView from "../views/RegisterView.vue";
import Explore from "../views/Explore.vue";
import Contact from "../views/Contact.vue";

import FlightsView from "../components/Flights.vue";
import ReservationsView from "../components/MyReservations.vue";
import ProfileView from "../components/Profile.vue";
import FlightDetailView from "../views/FlightDetailView.vue";
import SeatSelection from "../views/SeatSelection.vue";
import CartView from "../views/Cart.vue";
import CheckoutView from "../views/Checkout.vue";
import PaymentView from "../views/Payment.vue";
import PassengerDetails from "../views/PassengerDetails.vue";

import AdminLayout from "../views/admin/AdminLayout.vue";
import AdminDashboard from "../views/admin/AdminDashboard.vue";
import AdminFlights from "../views/admin/AdminFlights.vue";
import AdminAircrafts from "../views/admin/AdminAircrafts.vue";
import AdminReservations from "../views/admin/AdminReservations.vue";
import AdminUsers from "../views/admin/AdminUsers.vue";
import ReservationSuccess from "../views/ReservationSuccess.vue";

import "maplibre-gl/dist/maplibre-gl.css";

const routes = [
  { path: "/", component: Home },
  { path: "/explore", component: Explore },
  { path: "/flights", component: FlightsView },
  { path: "/flight/:id", component: FlightDetailView },

  { path: "/login", component: LoginView },
  { path: "/register", component: RegisterView },
  { path: "/contact", component: Contact },

  { path: "/reservations", component: ReservationsView },
  { path: "/profile", component: ProfileView },

  { path: "/cart", component: CartView },
  { path: "/checkout", component: CheckoutView },
  { path: "/passengers", name: "Passengers", component: PassengerDetails},
  { path: "/payment", component: PaymentView },
  { path: "/seats/:id", component: SeatSelection },
  { path: "/reservation-success/:id", component: ReservationSuccess},
  { path: "/check-in/:orderId", name: "CheckIn", component: () => import("../views/CheckIn.vue")},
  { path: "/auth/google/callback", component: () => import("../views/GoogleCallback.vue")},


  {
    path: "/admin",
    component: AdminLayout,
    meta: { requiresAdmin: true },
    children: [
      { path: "", component: AdminDashboard },
      { path: "flights", component: AdminFlights },
      { path: "aircrafts", component: AdminAircrafts },
      { path: "reservations", component: AdminReservations },
      { path: "users", component: AdminUsers }
    ]
  }
];

const router = createRouter({
  history: createWebHashHistory(),
  routes
});

router.beforeEach((to, from, next) => {
  const userRaw = localStorage.getItem("user");
  const user = userRaw ? JSON.parse(userRaw) : null;

  if (to.meta.requiresAdmin && (!user || user.role !== "admin")) {
    return next("/");
  }

  next();
});

export default router;