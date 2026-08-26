'use client';

import React, { createContext, useContext, useState, useEffect } from 'react';
import { NotificationItem } from '@/types/notification';
import { NotificationService } from '@/services/api/notification.service';

interface NotificationContextType {
  notifications: NotificationItem[];
  unreadCount: number;
  isDrawerOpen: boolean;
  openDrawer: () => void;
  closeDrawer: () => void;
  markAsRead: (id: string) => Promise<void>;
  markAllAsRead: () => Promise<void>;
  refreshNotifications: () => Promise<void>;
}

const NotificationContext = createContext<NotificationContextType | undefined>(undefined);

export function NotificationProvider({ children }: { children: React.ReactNode }) {
  const [notifications, setNotifications] = useState<NotificationItem[]>([]);
  const [isDrawerOpen, setIsDrawerOpen] = useState(false);

  const fetchNotifs = async () => {
    const list = await NotificationService.getNotifications();
    setNotifications(list);
  };

  useEffect(() => {
    fetchNotifs();
  }, []);

  const unreadCount = notifications.filter((n) => !n.isRead).length;

  const markAsRead = async (id: string) => {
    await NotificationService.markAsRead(id);
    setNotifications((prev) =>
      prev.map((n) => (n.id === id ? { ...n, isRead: true } : n))
    );
  };

  const markAllAsRead = async () => {
    await NotificationService.markAllAsRead();
    setNotifications((prev) => prev.map((n) => ({ ...n, isRead: true })));
  };

  return (
    <NotificationContext.Provider
      value={{
        notifications,
        unreadCount,
        isDrawerOpen,
        openDrawer: () => setIsDrawerOpen(true),
        closeDrawer: () => setIsDrawerOpen(false),
        markAsRead,
        markAllAsRead,
        refreshNotifications: fetchNotifs,
      }}
    >
      {children}
    </NotificationContext.Provider>
  );
}

export function useNotifications() {
  const context = useContext(NotificationContext);
  if (!context) {
    throw new Error('useNotifications must be used within a NotificationProvider');
  }
  return context;
}
