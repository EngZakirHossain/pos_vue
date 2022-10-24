import Vue from 'vue'
import Card from './Card'
import Child from './Child'
import Modal from './Modal'
import Search from './Search'
import Button from './Button'
import Checkbox from './Checkbox'
import TopClients from './TopClients'
import StockAlert from './StockAlert'
import Pagination from './Pagination'
import EmptyTable from './EmptyTable'
import Breadcrumbs from './Breadcrumbs'
import CompanyInfo from './CompanyInfo'
import TableLoading from './TableLoading'
import RecentActivities from './RecentActivities'



import { HasError, AlertError, AlertSuccess } from 'vform'

// Components that are registered globaly.
[
  Card,
  Child,
  Modal,
  Button,
  Search,
  Checkbox,
  HasError,
  StockAlert,
  EmptyTable,
  TopClients,
  AlertError,
  Pagination,
  Breadcrumbs,
  CompanyInfo,
  AlertSuccess,
  TableLoading,
  RecentActivities

].forEach(Component => {
  Vue.component(Component.name, Component)
})
