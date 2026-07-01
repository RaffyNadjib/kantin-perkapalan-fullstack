import React, { useState } from 'react';
import { View, Text, TextInput, TouchableOpacity, StyleSheet, SafeAreaView, StatusBar, Alert, Platform } from 'react-native';

export default function LoginScreen({ navigation }) {
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');

  // PASTIKAN IP INI SAMA DENGAN IP DI KEEMPAT FILE LAINNYA
  // Untuk LoginScreen.js:
  const API_URL = "http://10.234.56.211:3000/api/login";

  const handleLogin = async () => {
    if (!username || !password) {
      Alert.alert('Peringatan', 'Username dan password wajib diisi!');
      return;
    }

    try {
      const response = await fetch(API_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ username, password })
      });
      const result = await response.json();

      if (result.success) {
        // Jika sukses, pindah ke halaman utama
        navigation.replace('MainApp', { role: result.role || 'kasir' });
      } else {
        // Jika gagal, tampilkan alasan dari server
        Alert.alert('Gagal Login', result.message || 'Terjadi kesalahan tidak dikenal');
      }
    } catch (error) {
      Alert.alert('Koneksi Terputus', 'Server tidak merespon. Cek IP Address!');
    }
  };

  return (
    <SafeAreaView style={styles.safeArea}>
      <StatusBar backgroundColor="#0A1D37" barStyle="light-content" />
      <View style={styles.container}>
        <Text style={styles.title}>Kantin Perkapalan</Text>
        <Text style={styles.subtitle}>Sistem Kasir Mobile</Text>
        
        <TextInput style={styles.input} placeholder="Username" value={username} onChangeText={setUsername} autoCapitalize="none" />
        <TextInput style={styles.input} placeholder="Password" secureTextEntry value={password} onChangeText={setPassword} />
        
        <TouchableOpacity style={styles.btnLogin} onPress={handleLogin}>
          <Text style={styles.btnText}>Masuk</Text>
        </TouchableOpacity>
      </View>
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safeArea: { flex: 1, backgroundColor: '#F0F4F8', paddingTop: Platform.OS === 'android' ? StatusBar.currentHeight : 0, paddingBottom: Platform.OS === 'android' ? 20 : 0 },
  container: { flex: 1, justifyContent: 'center', padding: 20 },
  title: { fontSize: 32, fontWeight: 'bold', color: '#0A1D37', textAlign: 'center' },
  subtitle: { fontSize: 16, color: '#2980B9', textAlign: 'center', marginBottom: 40 },
  input: { backgroundColor: '#FFF', borderWidth: 1, borderColor: '#CBD5E1', padding: 14, borderRadius: 8, marginBottom: 15 },
  btnLogin: { backgroundColor: '#0A1D37', padding: 15, borderRadius: 8, alignItems: 'center' },
  btnText: { color: '#FFF', fontWeight: 'bold', fontSize: 16 }
});