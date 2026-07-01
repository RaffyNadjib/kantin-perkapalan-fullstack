import React from 'react';
import { Platform } from 'react-native';
import { NavigationContainer } from '@react-navigation/native';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { Ionicons } from '@expo/vector-icons';

import LoginScreen from './screens/LoginScreen';
import DashboardScreen from './screens/DashboardScreen';
import KasirScreen from './screens/KasirScreen';
import AdminScreen from './screens/AdminScreen'; 
import LogbookScreen from './screens/LogbookScreen';

const Stack = createNativeStackNavigator();
const Tab = createBottomTabNavigator();

// Menerima parameter { route } dari LoginScreen
function MainTabNavigator({ route }) {
  
  // Tangkap role pembawa dari form login (default ke 'kasir' demi keamanan)
  const userRole = route.params?.role || 'kasir';

  return (
    <Tab.Navigator
      screenOptions={({ route }) => ({
        headerShown: false,
        tabBarIcon: ({ focused, color, size }) => {
          let iconName = route.name === 'Dashboard' ? 'home' : route.name === 'Kasir' ? 'calculator' : route.name === 'Gudang' ? 'cube' : 'book';
          return <Ionicons name={focused ? iconName : `${iconName}-outline`} size={size} color={color} />;
        },
        tabBarActiveTintColor: '#38BDF8',
        tabBarInactiveTintColor: '#94A3B8',
        tabBarStyle: {
          backgroundColor: '#0F172A',
          height: Platform.OS === 'android' ? 90 : 85,
          paddingBottom: Platform.OS === 'android' ? 35 : 30,
          paddingTop: 10,
        }
      })}
    >
      {/* 🟢 Menu Utama: Bisa diakses oleh admin DAN kasir */}
      <Tab.Screen name="Dashboard" component={DashboardScreen} />
      <Tab.Screen name="Kasir" component={KasirScreen} />
      
      {/* 🔴 Menu Rahasia: HANYA muncul jika login sebagai admin */}
      {userRole === 'admin' && (
        <>
          <Tab.Screen name="Gudang" component={AdminScreen} />
          <Tab.Screen name="Logbook" component={LogbookScreen} />
        </>
      )}
    </Tab.Navigator>
  );
}

export default function App() {
  return (
    <NavigationContainer>
      <Stack.Navigator initialRouteName="Login" screenOptions={{ headerShown: false }}>
        <Stack.Screen name="Login" component={LoginScreen} />
        <Stack.Screen name="MainApp" component={MainTabNavigator} />
      </Stack.Navigator>
    </NavigationContainer>
  );
}